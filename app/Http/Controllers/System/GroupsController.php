<?php

namespace Servidor\Http\Controllers\System;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Servidor\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class GroupsController extends Controller
{
    const GROUP_NAME_TAKEN = 9;
    const GROUP_GID_TAKEN = 4;
    const GROUP_SYNTAX_INVALID = 2;
    const GROUP_OPTION_INVALID = 3;
    const GROUP_UPDATE_FAILED = 10;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        exec('cat /etc/group', $lines);

        $keys = ['name', 'password', 'gid', 'users'];
        $groups = collect();

        foreach ($lines as $line) {
            $group = array_combine($keys, explode(':', $line));
            $group['users'] = '' == $group['users'] ? [] : explode(',', $group['users']);

            $groups->push($group);
        }

        return $groups;
    }

    /**
     * Create a new group on the host system.
     */
    public function store(Request $request)
    {
        $options = [];
        $data = $request->validate($this->validationRules());

        if ((int) ($data['gid'] ?? null) > 0) {
            $options[] = '-g ' . (int) $data['gid'];
        }

        $options[] = $data['name'];

        exec('sudo groupadd ' . implode(' ', $options), $output, $retval);
        unset($output);

        if ($data['users'] ?? null === null) {
            $data['users'] = '';
        }

        switch ($retval) {
            case 0:
                $group = posix_getgrnam($data['name']);

                $data = [
                    'gid' => $group['gid'],
                    'name' => $group['name'],
                    'users' => $group['members'],
                ];

                break;
            case self::GROUP_SYNTAX_INVALID:
                $data['error'] = 'Invalid command syntax.';
                break;
            case self::GROUP_OPTION_INVALID:
                $data['error'] = 'Invalid argument to option';
                break;
            case self::GROUP_GID_TAKEN:
                $data['error'] = 'GID not unique (when -o not used)';
                break;
            case self::GROUP_NAME_TAKEN:
                $data['error'] = 'Group name not unique';
                break;
            case self::GROUP_UPDATE_FAILED:
                $data['error'] = "Can't update group file";
                break;
        }

        return response($data, 0 === $retval
            ? Response::HTTP_CREATED
            : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Update the specified group on the system.
     */
    public function update(Request $request, int $gid)
    {
        $options = [];
        $data = $request->validate($this->validationRules());
        $data['gid'] = (int) ($data['gid'] ?? $gid);

        if (!$original = posix_getgrgid($gid)) {
            throw $this->failed('No group found matching the given criteria.');
        }
        $updated = $original;

        if ($data['name'] != $original['name']) {
            $options[] = '-n ' . $data['name'];
        }

        if ($data['gid'] != $gid && $data['gid'] > 0) {
            $options[] = '-g ' . $data['gid'];
        }

        if (($data['users'] ?? []) != $original['members']) {
            $members = implode(',', $data['users']);
        }

        if (empty($options) && !isset($members)) {
            throw $this->failed('Nothing to update!');
        }

        if (count($options)) {
            $options[] = $original['name'];

            exec('sudo groupmod ' . implode(' ', $options), $output, $retval);

            if (0 !== $retval) {
                throw $this->failed('Something went wrong. Exit code: ' . $retval, 'exec');
            }

            $updated = posix_getgrgid($data['gid']);
        }

        if ($members ?? null) {
            $group = $updated['name'];

            exec("sudo gpasswd -M '" . ($members ?? null) . "' {$group}", $output, $retval);

            if (0 !== $retval) {
                throw $this->failed('Something went wrong. Exit code: ' . $retval, 'exec');
            }

            $updated = posix_getgrgid($data['gid']);
        }

        return response([
            'gid' => $updated['gid'],
            'name' => $updated['name'],
            'users' => $updated['members'],
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified group from the system.
     */
    public function destroy(int $gid)
    {
        if ($group = posix_getgrgid($gid)) {
            exec('sudo groupdel ' . $group['name']);
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the validation rules for system groups.
     */
    protected function validationRules(): array
    {
        return [
            'name' => [
                'required', 'max:32', 'bail',
                function ($attribute, $value, $fail) {
                    if (str_contains($value, ':')) {
                        $fail("The {$attribute} cannot contain a colon.");
                    }

                    if (str_contains($value, ',')) {
                        $fail("The {$attribute} cannot contain a comma.");
                    }

                    if (str_contains($value, ["\t", "\n", ' '])) {
                        $fail("The {$attribute} cannot contain whitespace or newlines.");
                    }
                },
                'regex:/^[a-z_][a-z0-9_-]*[\$]?$/',
            ],
            'gid' => 'integer|nullable',
            'groups' => 'array|nullable',
            'users' => 'array|nullable',
        ];
    }

    protected function failed($message, $key = 'gid')
    {
        return ValidationException::withMessages([
            $key => $message,
        ]);
    }
}

<?php

namespace Servidor\Projects\Redirects;

use Servidor\Events\ProjectProgress;
use Servidor\Projects\ProgressStep;

class ApplyRedirectNginxConfig
{
    public function handle(ProjectRedirectSaved $event): void
    {
        /** @var \Servidor\Projects\Project */
        $project = $event->redirect->project;

        $step = new ProgressStep('nginx.save', 'Saving nginx config', 60);
        ProjectProgress::dispatch($project, $step);

        $event->redirect->writeNginxConfig();

        $project->is_enabled ? $event->redirect->enable() : $event->redirect->disable();

        ProjectProgress::dispatch($project, $step->complete());
    }
}

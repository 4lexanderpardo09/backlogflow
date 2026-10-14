<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\Calendar;
use App\Helpers\ProjectScope;
use App\Helpers\SprintProgress;
use App\Models\Developer;
use App\Models\Project;
use App\Services\Projects\CronogramaService;

class CronogramaController extends Controller
{
    private const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
        'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    public function indexAction(): void
    {
        $view = $this->input('vista') === 'linea' ? 'linea' : 'calendario';
        $projects = (new Project())->all('name ASC');
        $filters = [
            'project_id' => (int) $this->input('project_id', 0),
            'child_id' => (int) $this->input('child_id', 0),
            'developer_id' => (int) $this->input('developer_id', 0),
        ];
        // Projects that feed the view; null = all of them (only the calendar allows that).
        $projectIds = ProjectScope::ids($projects, $filters['project_id'], $filters['child_id']);
        $service = new CronogramaService();
        $today = date('Y-m-d');

        $data = [
            'pageTitle' => 'Cronograma',
            'activeModule' => 'projects-cronograma',
            'mode' => $view,
            'filters' => $filters,
            'topProjects' => ProjectScope::topLevel($projects),
            'childProjects' => ProjectScope::childrenOf($projects, $filters['project_id']),
            'developers' => (new Developer())->allForList(),
            'today' => $today,
        ];

        if ($view === 'linea') {
            // Optional window; without both dates the service picks it (sprint span, else this month).
            $from = $this->dateInput('desde');
            $to = $this->dateInput('hasta');
            $data += [
                'from' => $from,
                'to' => $to,
                'gantt' => $projectIds === null
                    ? ['window_start' => date('Y-m-01'), 'window_end' => date('Y-m-t'), 'rows' => []]
                    : $service->gantt($projectIds, $from, $to),
            ];
        } else {
            $month = preg_match('/^(\d{4})-(\d{2})$/', (string) $this->input('mes', ''), $m) === 1 && (int) $m[2] >= 1 && (int) $m[2] <= 12
                ? [(int) $m[1], (int) $m[2]]
                : [(int) date('Y'), (int) date('n')];
            $monthStart = sprintf('%04d-%02d-01', $month[0], $month[1]);
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $weeks = Calendar::monthWeeks($month[0], $month[1]);
            $events = $service->calendarEvents(
                $projectIds,
                $weeks[0]['start'],
                end($weeks)['days'][6],
                $filters['developer_id'],
                $today
            );
            $inMonth = array_values(array_filter($events, fn (array $e) => $e['end'] >= $monthStart && $e['start'] <= $monthEnd));
            $nextWeek = date('Y-m-d', strtotime($today . ' +7 days'));

            $data += [
                'weeks' => $weeks,
                'events' => $events,
                'monthStart' => $monthStart,
                'monthLabel' => self::MONTHS[$month[1]] . ' ' . $month[0],
                'prevMonth' => date('Y-m', strtotime($monthStart . ' -1 month')),
                'nextMonth' => date('Y-m', strtotime($monthStart . ' +1 month')),
                'counts' => SprintProgress::statusCounts(array_map(fn (array $e) => ['system_status' => $e['status']], $inMonth)),
                'monthTotal' => count($inMonth),
                'dueSoon' => count(array_filter($events, fn (array $e) => $e['status'] !== 'completed' && $e['end'] >= $today && $e['end'] <= $nextWeek)),
            ];
        }

        $this->render('projects/cronograma/index', $data);
    }
}

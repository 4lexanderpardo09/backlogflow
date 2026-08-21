<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\IdeaNote;
use App\Models\Project;

/**
 * Trello-style capture board: quick notes/tasks that come up in sprint
 * review meetings, grouped by column (status), before they're clarified
 * enough to become a real backlog item.
 */
class IdeasController extends Controller
{
    private const STATUSES = ['new', 'clarifying', 'ready', 'converted'];

    public function indexAction(): void
    {
        $projectFilter = (int) $this->input('project_id', 0);
        $noteModel = new IdeaNote();
        $notes = $projectFilter > 0 ? $noteModel->byProject($projectFilter) : $noteModel->allWithDetails();

        $columns = array_fill_keys(self::STATUSES, []);
        foreach ($notes as $note) {
            $columns[$note['status']][] = $note;
        }

        $this->render('projects/ideas/board', [
            'pageTitle' => 'Ideas',
            'activeModule' => 'projects-ideas',
            'columns' => $columns,
            'projects' => (new Project())->all('name ASC'),
            'projectFilter' => $projectFilter,
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $text = trim((string) $this->input('text'));
            $projectId = (int) $this->input('project_id');

            if ($text !== '' && $projectId > 0) {
                (new IdeaNote())->insert([
                    'project_id' => $projectId,
                    'text' => $text,
                    'created_by' => $this->input('created_by') ?: null,
                    'status' => 'new',
                ]);
                $this->flash('success', 'Nota agregada al tablero.');
            }
        }

        $this->redirect('projects/ideas/index');
    }

    /** AJAX endpoint used by the drag-and-drop board to move a card between columns. */
    public function moveAction(?string $id): void
    {
        $status = (string) $this->input('status');

        if (!in_array($status, self::STATUSES, true)) {
            $this->json(['ok' => false, 'error' => 'invalid_status']);
            return;
        }

        (new IdeaNote())->updateStatus((int) $id, $status);
        $this->json(['ok' => true]);
    }

    public function deleteAction(?string $id): void
    {
        (new IdeaNote())->delete((int) $id);
        $this->flash('success', 'Nota eliminada.');
        $this->redirect('projects/ideas/index');
    }

    /** Sends the user to the backlog create form pre-filled from this note. */
    public function convertAction(?string $id): void
    {
        $this->redirect('projects/backlog/create?from_note=' . (int) $id);
    }
}

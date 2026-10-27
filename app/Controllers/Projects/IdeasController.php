<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\IdeaNote;
use App\Models\Project;

/**
 * Trello-style capture board: quick notes/tasks that come up in sprint
 * review meetings, grouped by column (status), before they're clarified
 * enough to become a real backlog item. A note may be tied to a project or
 * be a loose idea (no project yet): a new idea shouldn't force creating a
 * project first.
 */
class IdeasController extends Controller
{
    private const STATUSES = ['new', 'clarifying', 'ready', 'converted'];

    public function indexAction(): void
    {
        // "none" = only the loose ideas; a project id = that project's; empty = all.
        $projectFilter = (string) $this->input('project_id', '');
        $noteModel = new IdeaNote();
        $notes = match (true) {
            $projectFilter === 'none' => $noteModel->withoutProject(),
            (int) $projectFilter > 0 => $noteModel->byProject((int) $projectFilter),
            default => $noteModel->allWithDetails(),
        };

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

            if ($text !== '') {
                (new IdeaNote())->insert([
                    'project_id' => $this->projectIdInput(),
                    'text' => $text,
                    'created_by' => $this->input('created_by') ?: null,
                    'status' => 'new',
                ]);
                $this->flash('success', 'Nota agregada al tablero.');
            }
        }

        $this->redirect('projects/ideas/index');
    }

    public function editAction(?string $id): void
    {
        $noteModel = new IdeaNote();
        $note = $noteModel->findWithDetails((int) $id);

        if ($note === null) {
            $this->redirect('projects/ideas/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $text = trim((string) $this->input('text'));

            if ($text !== '') {
                $noteModel->update((int) $id, [
                    'project_id' => $this->projectIdInput(),
                    'text' => $text,
                    'created_by' => $this->input('created_by') ?: null,
                ]);
                $this->flash('success', 'Nota actualizada.');
            }

            $this->redirect('projects/ideas/index');
            return;
        }

        $this->render('projects/ideas/edit', [
            'pageTitle' => 'Editar nota',
            'activeModule' => 'projects-ideas',
            'note' => $note,
            'projects' => (new Project())->all('name ASC'),
        ]);
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

    /** The chosen project, or null for a loose idea (empty choice or a project that doesn't exist). */
    private function projectIdInput(): ?int
    {
        $projectId = (int) $this->input('project_id', 0);

        return $projectId > 0 && (new Project())->find($projectId) !== null ? $projectId : null;
    }

    /** Sends the user to the backlog create form pre-filled from this note. */
    public function convertAction(?string $id): void
    {
        $this->redirect('projects/backlog/create&from_note=' . (int) $id);
    }
}

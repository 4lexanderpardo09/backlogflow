(function () {
    var board = document.querySelector('[data-idea-board]');
    if (!board) return;

    var draggedCard = null;

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('[data-idea-card]');
        if (!card || card.getAttribute('draggable') !== 'true') return;
        draggedCard = card;
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(function () { card.classList.add('idea-card-dragging'); }, 0);
    });

    board.addEventListener('dragend', function () {
        if (draggedCard) draggedCard.classList.remove('idea-card-dragging');
        draggedCard = null;
        board.querySelectorAll('[data-idea-dropzone]').forEach(function (zone) {
            zone.classList.remove('idea-dropzone-over');
        });
    });

    board.querySelectorAll('[data-idea-dropzone]').forEach(function (zone) {
        zone.addEventListener('dragover', function (e) {
            if (!draggedCard) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('idea-dropzone-over');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('idea-dropzone-over');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('idea-dropzone-over');
            if (!draggedCard) return;

            var id = draggedCard.getAttribute('data-id');
            var status = zone.getAttribute('data-status');
            var placeholder = zone.querySelector('.empty-state');
            if (placeholder) placeholder.remove();
            zone.appendChild(draggedCard);

            fetch('/index.php?r=projects/ideas/move/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'status=' + encodeURIComponent(status)
            }).then(function (res) {
                if (!res.ok) window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        });
    });
})();

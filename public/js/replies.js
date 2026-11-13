// Optional challenge: collapse / expand reply branches.
// Buttons are created only after this script runs, so with JavaScript disabled
// every reply stays visible and no useless controls are shown.
// No comment text is read or inserted here; we only toggle existing elements.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.comment[data-replies]').forEach(function (comment) {
        var container = document.getElementById(comment.dataset.replies);
        var actions = comment.querySelector(':scope > .comment-actions');
        if (!container || !actions) return;

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'toggle-replies';
        button.setAttribute('aria-controls', container.id);
        button.setAttribute('aria-expanded', 'true');
        button.textContent = 'Hide replies';

        button.addEventListener('click', function () {
            var expanded = button.getAttribute('aria-expanded') === 'true';
            container.hidden = expanded;
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            button.textContent = expanded ? 'Show replies' : 'Hide replies';
        });

        actions.prepend(button);
    });
});

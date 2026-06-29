(function () {
    var roleSelect = document.getElementById('role_id');
    var panel      = document.querySelector('[data-permissions-panel]');
    if (!roleSelect || !panel) { return; }
    function syncPermissionsPanel() {
        var selected = roleSelect.options[roleSelect.selectedIndex];
        var roleName = selected ? selected.getAttribute('data-role-name') : 'user';
        panel.classList.toggle('is-hidden', roleName !== 'user');
    }
    roleSelect.addEventListener('change', syncPermissionsPanel);
    syncPermissionsPanel();
}());

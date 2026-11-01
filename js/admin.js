document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const menuToggle = document.querySelector('.menu-toggle');

  if (sidebar && menuToggle) {
    menuToggle.addEventListener('click', () => {
      const isOpen = sidebar.classList.toggle('open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => sidebar.classList.remove('open'));
    });
  }

  const searchInput = document.getElementById('record-search');
  const rows = document.querySelectorAll('#records-body tr');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const searchTerm = searchInput.value.trim().toLowerCase();
      rows.forEach((row) => {
        row.hidden = !row.textContent.toLowerCase().includes(searchTerm);
      });
    });
  }

  const dialog = document.getElementById('edit-dialog');
  const editForm = document.getElementById('edit-form');
  const closeDialogButtons = document.querySelectorAll('.close-dialog');

  const closeDialog = () => {
    if (!dialog) return;
    if (typeof dialog.close === 'function') {
      dialog.close();
    } else {
      dialog.removeAttribute('open');
    }
  };

  closeDialogButtons.forEach((button) => button.addEventListener('click', closeDialog));

  document.querySelectorAll('.edit-record').forEach((button) => {
    button.addEventListener('click', () => {
      if (!dialog || !editForm) return;

      let record;
      try {
        record = JSON.parse(button.dataset.record || '{}');
      } catch (error) {
        return;
      }

      editForm.reset();
      Object.entries(record).forEach(([key, value]) => {
        const field = editForm.querySelector(`[name="${key}"]`);
        if (!field) return;
        // Browser security prevents pre-filling file inputs. Existing images are
        // therefore preserved server-side unless a replacement is selected.
        if (field.type !== 'file') field.value = value ?? '';
      });

      const currentImageNote = editForm.querySelector('[data-current-image]');
      if (currentImageNote) {
        currentImageNote.textContent = record.image ? `Current image: ${String(record.image).split(/[\\/]/).pop()}` : 'No image currently uploaded.';
      }

      if (typeof dialog.showModal === 'function') {
        dialog.showModal();
      } else {
        dialog.setAttribute('open', '');
      }
    });
  });

  document.querySelectorAll('.delete-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm('Delete this record? This action cannot be undone.')) {
        event.preventDefault();
      }
    });
  });
});

document.addEventListener('DOMContentLoaded', function () {
  // Находим оба чекбокса в заголовках (верхний и нижний)
  const selectAllCheckboxes = document.querySelectorAll('.manage-column input[type="checkbox"]');

  if (selectAllCheckboxes.length > 0) {
      selectAllCheckboxes.forEach(selectAll => {
          selectAll.addEventListener('change', function () {
              const checkboxes = document.querySelectorAll('.cb-select');
              checkboxes.forEach(checkbox => {
                  checkbox.checked = selectAll.checked;
              });

              // Делаем так, чтобы второй чекбокс тоже переключался
              selectAllCheckboxes.forEach(otherCheckbox => {
                  if (otherCheckbox !== selectAll) {
                      otherCheckbox.checked = selectAll.checked;
                  }
              });
          });
      });
  }
});

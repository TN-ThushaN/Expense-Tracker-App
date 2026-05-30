document.querySelectorAll('.type-opt').forEach(label => {
  label.addEventListener('click', () => {
    document.querySelectorAll('.type-opt').forEach(l => {
      l.classList.remove('active-expense', 'active-income');
    });
    const val = label.querySelector('input').value;
    label.classList.add(val === 'expense' ? 'active-expense' : 'active-income');
  });
});
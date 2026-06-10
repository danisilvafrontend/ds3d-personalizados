document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('simulatorForm');
  const btn = document.getElementById('btnEstimate');
  const output = document.getElementById('estimatedValue');

  if (!form || !btn || !output) return;

  const calculate = async () => {
    const data = new FormData(form);

    try {
      const response = await fetch('ajax/calcular_orcamento.php', {
        method: 'POST',
        body: data
      });

      const result = await response.json();

      if (result.success) {
        output.textContent = `R$ ${result.valor}`;
      } else {
        output.textContent = 'R$ 0,00';
        alert(result.message || 'Não foi possível calcular.');
      }
    } catch (error) {
      output.textContent = 'R$ 0,00';
      alert('Erro ao calcular a estimativa.');
    }
  };

  btn.addEventListener('click', calculate);

  form.querySelectorAll('select, input').forEach((field) => {
    field.addEventListener('change', calculate);
  });
});

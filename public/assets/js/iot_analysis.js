(function(){
  // --- Entrada segura: pega o JSON embutido no data-json do elemento #iot-history ---
  // Se o elemento ou o dataset não existir, usa string '[]' (array vazio).
  const raw = document.getElementById('iot-history')?.dataset?.json || '[]';

  // Variável que conterá o histórico parseado
  let history = [];

  // Tenta parsear o JSON; captura erro e loga no console em caso de problema
  try {
    history = JSON.parse(raw);
  } catch(e) {
    console.error('Falha ao parsear JSON iot-history:', e);
    history = []; // garante formato consistente mesmo em erro
  }

  // Se não houver dados, avisa e interrompe execução (nada a desenhar)
  if (!history.length) {
    console.warn('Histórico vazio — nada para desenhar.');
    return;
  }

  // --- Ordena por timestamp ascendente (importante para labels e séries temporais) ---
  // Garante que as labels e os valores estejam em ordem cronológica
  history.sort((a,b) => (new Date(a.timestamp)) - (new Date(b.timestamp)));

  // --- Prepara arrays de labels e valores (fallbacks seguros) ---
  // labels: transformamos timestamp ISO em string legível (substitui 'T' por espaço)
  const labels = history.map(r => (r.timestamp || '').replace('T',' ')); // string legível

  // energyValues: tenta extrair r.energy.instant convertendo em Number quando possível
  const energyValues = history.map(r => {
    const v = r.energy && r.energy.instant != null ? Number(r.energy.instant) : null;
    return Number.isFinite(v) ? v : null; // normaliza para null quando inválido
  });

  // co2Values: extrai co2 convertendo em Number quando possível
  const co2Values = history.map(r => {
    const v = r.co2 != null ? Number(r.co2) : null;
    return Number.isFinite(v) ? v : null;
  });

  // --- Helper: filtra entradas válidas mantendo alinhamento label↔value ---
  // Alguns pontos podem ter valores nulos — muitos gráficos preferem arrays sem nulls
  // Esta função retorna arrays com apenas os índices válidos, preservando labels correspondentes.
  function maskValid(labels, values) {
    const L = [], V = [];
    for (let i=0;i<labels.length;i++){
      if (values[i] != null && !Number.isNaN(values[i])) {
        L.push(labels[i]);
        V.push(values[i]);
      }
    }
    return { labels: L, values: V };
  }

  // Aplica máscara para energia e CO2 (remove pontos inválidos)
  const energy = maskValid(labels, energyValues);
  const co2 = maskValid(labels, co2Values);

  // --- Helper para desenhar linha usando escala do tipo 'category' (Chart.js) ---
  // canvasId: id do <canvas> no DOM
  // labelsArr: array de labels (strings)
  // dataArr: array de valores numéricos
  // label: rótulo da série exibido na legenda
  // yLabel: texto do eixo Y
  // color: cor da linha (string CSS rgba ou hex)
  function drawCategoryLine(canvasId, labelsArr, dataArr, label, yLabel, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) { 
      console.warn('Canvas não encontrado', canvasId);
      return;
    }

    // Se já existir um gráfico nesse canvas, destrói para evitar duplicação
    try { 
      const old = Chart.getChart(canvas);
      if (old) old.destroy();
    } catch(e){
      // ignore — Chart.getChart pode não existir em versões muito antigas
    }

    // Cria novo gráfico de linha
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: labelsArr,
        datasets: [{
          label: label,
          data: dataArr,
          borderColor: color || 'rgba(54,162,235,0.9)',
          backgroundColor: 'transparent',
          tension: 0.25,      // suaviza a curva (0 -> linhas retas)
          pointRadius: 2,     // tamanho dos pontos
          borderWidth: 2,     // espessura da linha
        }]
      },
      options: {
        aspectRatio: 8,          // controla proporção do canvas (largura/altura)
        maintainAspectRatio: true,
        scales: {
          x: {
            type: 'category',    // usa labels como categorias (strings)
            ticks: { 
              maxRotation: 45,   // evita rotação excessiva das labels
              autoSkip: true, 
              maxTicksLimit: 12  // limita quantidade de ticks para evitar poluição
            }
          },
          y: {
            title: { display: true, text: yLabel || '' } // rótulo do eixo Y
          }
        },
        plugins: {
          legend: { display: true },  // exibe legenda
          tooltip: { mode: 'index', intersect: false } // tooltip mostra valores por índice
        },
        interaction: { mode: 'index', intersect: false } // comportamento de interação
      }
    });
  }

  // --- Desenha os gráficos invocando a helper com parâmetros ---
  drawCategoryLine('chartEnergy', energy.labels, energy.values, 'Energia (kW)', 'kW', 'rgba(54,162,235,0.9)');
  drawCategoryLine('chartCO2', co2.labels, co2.values, 'CO₂ (ppm)', 'ppm', 'rgba(40,167,69,0.9)');

})();

/* ============================================================
   BLOCO DA BARRA DE STATUS (progress bar animada)
   ============================================================ */

$(function(){

  var $status = $('#singleStatus');

  if (!$status.length) {
    console.warn('Elemento #singleStatus não encontrado.');
    return;
  }

  // tenta ler valor de vários locais possíveis
  var raw = 
    $status.attr('data-value') || 
    $status.data('value') || 
    $status.find('.status-fill').data('value');

  var value = parseFloat(raw);

  if (isNaN(value)) {
    console.warn('data-value inválido ou ausente. Usando 0.');
    value = 0;
  }

  // limita entre 0 e 100%
  value = Math.max(0, Math.min(100, value));

  // elementos internos
  var $percent = $status.find('.status-percent');
  var $fill = $status.find('.status-fill');
  var $bar = $status.find('.status-bar');

  // atualiza ARIA
  $bar.attr('aria-valuenow', value);

  // texto percentual visível
  $percent.text(value.toFixed(2) + '%');

  // pequena espera para permitir reprodução da animação CSS
  setTimeout(function(){
    $fill.css('width', value + '%');
  }, 20);

  // após animação, decide se texto precisa inverter cor
  setTimeout(function(){

    var barWidth = $bar.width();
    var fillPx = Math.round(barWidth * (value / 100));

    var percentOffset = $percent.position().left + $percent.outerWidth();

    if (fillPx > (percentOffset - 10)) {
      $percent.addClass('on-fill');  // texto dentro da barra
    } else {
      $percent.removeClass('on-fill');
    }

  }, 950); // coincide com a duração da animação CSS



  // debug opcional
  setTimeout(function(){
    var visible = 
      $bar.is(':visible') && 
      $bar.width() > 2 && 
      $fill.width() > 0;

    if (!visible) {
      console.warn(
        'Barra pode estar invisível. Possíveis causas:\n' +
        '- display:none no pai\n' +
        '- CSS conflitante\n' +
        '- script executado antes do DOM\n' +
        '- overflow hidden\n' +
        '- z-index incorreto'
      );
    }
  }, 1200);

});


// Intervalo entre requisições (polling)
const POLL_INTERVAL = 3000;

// Arrays que armazenam os últimos pontos dos gráficos
let labels = [], temps = [], hums = [], energy = [];

// Instâncias dos gráficos Chart.js
let tempHumChart, energyChart, o2Chart, co2Chart;

// Máximo de pontos exibidos
const MAX_POINTS = 20;


// -------------------------------------------------------
// Inicializa todos os gráficos
// -------------------------------------------------------
function initCharts() {

  tempHumChart = new Chart($("#tempHumChart"), {
    type: 'line',
    data: { 
      labels,
      datasets: [
        { label:'Temperatura (°C)', data: temps },
        { label:'Umidade (%)', data: hums }
      ]
    },
    options: { animation:{duration:400}, maintainAspectRatio:true, aspectRatio:2.2 }
  });

  energyChart = new Chart($("#energyChart"), {
    type: 'line',
    data: { 
      labels,
      datasets: [
        { label:'Energia (kW)', data: energy }
      ]
    },
    options: { animation:{duration:400}, maintainAspectRatio:true, aspectRatio:5 }
  });

  o2Chart = new Chart($("#o2Chart"), {
    type:'doughnut',
    data:{ datasets:[{ data:[0,100] }] },
    options:{
      cutout:'70%',
      rotation:-90,
      circumference:180,
      maintainAspectRatio:true,
      aspectRatio:1.7,
      plugins:{ legend:{display:false} }
    }
  });

  co2Chart = new Chart($("#co2Chart"), {
    type:'doughnut',
    data:{ datasets:[{ data:[0,2000] }] },
    options:{
      cutout:'70%',
      rotation:-90,
      circumference:180,
      maintainAspectRatio:true,
      aspectRatio:1.7,
      plugins:{ legend:{display:false} }
    }
  });
}


// -------------------------------------------------------
// Adiciona novos dados aos gráficos
// -------------------------------------------------------
function pushPoint(label, t, h, e) {
  labels.push(label);
  temps.push(t);
  hums.push(h);
  energy.push(e);

  if (labels.length > MAX_POINTS) {
    labels.shift();
    temps.shift();
    hums.shift();
    energy.shift();
  }
}


// -------------------------------------------------------
// Atualiza interface + gráficos em tempo real
// -------------------------------------------------------
function updateUI(data) {

  if (!data) return;

  // Valores em texto
  $("#tempVal").text(data.temperature);
  $("#humVal").text(data.humidity);
  $("#o2Val").text(data.oxygen);
  $("#co2Val").text(data.co2);
  $("#energyTotal").text(data.energy.total);
  $("#energyPeak").text(data.energy.peak);

  // Status de alerta
  $("#tempStatus").html(data.temperature > 26 
    ? "<span style='color:#c0392b'>ALERTA</span>"
    : "<span style='color:#27ae60'>OK</span>"
  );

  $("#humStatus").html((data.humidity < 35 || data.humidity > 65)
    ? "<span style='color:#c0392b'>ALERTA</span>"
    : "<span style='color:#27ae60'>OK</span>"
  );

  $("#o2Status").html(data.oxygen < 90
    ? "<span style='color:#c0392b'>ALERTA</span>"
    : "<span style='color:#27ae60'>OK</span>"
  );

  $("#co2Status").html(data.co2 > 1000
    ? "<span style='color:#c0392b'>ALERTA</span>"
    : "<span style='color:#27ae60'>OK</span>"
  );

  // Label de tempo formatada
  const lbl = new Date(
    data.received_at || data.timestamp || Date.now()
  ).toLocaleTimeString();

  // Atualiza arrays
  pushPoint(lbl, data.temperature, data.humidity, data.energy.instant);

  // Atualiza gráficos
  tempHumChart.update();
  energyChart.update();

  // Gauge O2
  let o2 = Math.max(0, Math.min(100, Number(data.oxygen)));
  o2Chart.data.datasets[0].data = [o2, 100 - o2];
  o2Chart.update();

  // Gauge CO2
  let co2v = Math.max(0, Math.min(2000, Number(data.co2)));
  co2Chart.data.datasets[0].data = [co2v, 2000 - co2v];
  co2Chart.update();
}


// -------------------------------------------------------
// Fetch IoT com fallback usando jQuery
// -------------------------------------------------------
function fetchIoT() {
  
  const tries = ['/index.php/iot/data', '/iot_data_direct.php'];

  let attemptIndex = 0;

  function tryNext() {
    if (attemptIndex >= tries.length) {
      console.warn("Nenhuma rota IoT respondeu.");
      return;
    }

    const url = tries[attemptIndex] + '?t=' + Date.now();

    $.ajax({
      url: url,
      method: "GET",
      cache: false,
      dataType: "json",
      success: function(json) {
        // Se não retornou {status: "..."}, é um pacote válido
        if (json && !json.status) {
          updateUI(json);
        }
      },
      error: function() {
        // tenta a próxima rota
        attemptIndex++;
        tryNext();
      }
    });
  }

  tryNext();
}


// -------------------------------------------------------
// Inicialização quando o DOM estiver carregado
// -------------------------------------------------------
$(document).ready(function() {

  initCharts();

  // Se veio valor pré-carregado do PHP
  if (typeof initialIoT !== 'undefined' && initialIoT) {
    updateUI(initialIoT);
  }

  fetchIoT();                       // Primeira busca  
  setInterval(fetchIoT, POLL_INTERVAL); // Polling contínuo

});

// Executa quando o DOM estiver carregado
$(document).ready(function() {

    // ============================
    // BOTÃO PARA ABRIR/FECHAR MENU
    // ============================
    $(".toggle").on("click", function() {
        // Alterna a classe "active" na navegação e no conteúdo principal
        $(".navigation, .main").toggleClass("active");
    });

    // =========================================================
    // ALTERAR SEÇÕES DA APLICAÇÃO AO CLICAR NOS LINKS DO MENU
    // =========================================================
    $(".navigation a[href^='#']").on("click", function(e) {
        e.preventDefault(); // evita comportamento padrão do link

        var target = $(this).attr("href"); // ID da seção associada

        // Oculta todas as seções e ativa apenas a clicada
        $(".section").removeClass("active").hide();
        $(target).fadeIn().addClass("active");

        // Gerencia visual do menu (item "hovered")
        $(".navigation li").removeClass("hovered");
        $(this).parent("li").addClass("hovered");

        // Fecha menu em telas pequenas
        $(".navigation, .main").removeClass("active");
    });

});



/* ======================================================================
   BLOCO RESPONSÁVEL PELO GRÁFICO DE BURNDOWN (Chart.js dinâmico)
   ====================================================================== */

(function($, window, document){
  'use strict';

  var burndownChart = null; // referência ao gráfico (evita recriar)



  // ---------------------------------------------
  // Função que lê JSON do atributo data-response
  // ---------------------------------------------
  function parseResponse(selector){
    var $el = $(selector);
    if(!$el.length) return null;

    var raw = $el.attr('data-response'); // tenta atributo normal

    // fallback caso venha via jQuery .data()
    if(typeof raw === 'undefined' || raw === null){
      var maybe = $el.data('response');
      if(typeof maybe === 'object') return maybe;
      raw = maybe;
    }

    if(!raw) return null;
    if(typeof raw === 'object') return raw;

    // tenta parsear JSON normalmente
    try{
      return JSON.parse(String(raw));
    }catch(e){
      // fallback tentando limpar escapes
      try{
        return JSON.parse(String(raw).replace(/\\'/g,"'"));
      }catch(err){
        return null;
      }
    }
  }



  // -----------------------------------------------
  // Agrupa itens por sprint_id (para o burndown)
  // -----------------------------------------------
  function group(arr){
    var out = {};
    (arr||[]).forEach(function(it){
      if(!it) return;
      var id = String(it.sprint_id);
      out[id] = out[id] || [];
      out[id].push({date: it.log_date, points: Number(it.points)});
    });

    // ordena por data dentro de cada sprint
    Object.keys(out).forEach(function(k){
      out[k].sort(function(a,b){
        return new Date(a.date) - new Date(b.date);
      });
    });
    return out;
  }



  // ------------------------------------------------------
  // Retorna união de todas as datas, ordenadas cronologicamente
  // ------------------------------------------------------
  function unionDates(arrs){
    var s = new Set();

    (arrs||[]).forEach(function(a){
      (a||[]).forEach(function(i){ s.add(i.date); });
    });

    var a = Array.from(s);
    a.sort(function(a,b){ return new Date(a) - new Date(b); });
    return a;
  }



  // ------------------------------------------------------
  // Alinha dados (ideal/real) pelas datas consolidadas
  // ------------------------------------------------------
  function align(series, dates){
    var map = {};
    (series||[]).forEach(function(i){ map[i.date] = i.points; });

    return dates.map(function(d){
      return map.hasOwnProperty(d) ? map[d] : null;
    });
  }



  // ---------------------------------------------
  // Formata datas (ex.: 05/02)
  // ---------------------------------------------
  function fmtDate(d){
    if(!d) return '—';
    var dt = new Date(d + 'T00:00:00');
    if(isNaN(dt)) return d;

    var day = String(dt.getDate()).padStart(2,'0');
    var month = String(dt.getMonth()+1).padStart(2,'0');

    return day + '/' + month;
  }



  // ------------------------------------------------------
  // Converte HEX para rgba(opacidade)
  // ------------------------------------------------------
  function hexToRgba(hex, alpha){
    hex = hex.replace('#','');
    var bigint = parseInt(hex,16);
    var r = (bigint >> 16) & 255;
    var g = (bigint >> 8) & 255;
    var b = bigint & 255;
    return 'rgba('+r+','+g+','+b+','+alpha+')';
  }



  // ------------------------------------------------------
  // Monta datasets para todas as sprints
  // ------------------------------------------------------
  function buildDatasets(ids, idealG, realG, dates){
    // paleta de cores (loops caso tenha mais sprints)
    var palette = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f'];
    var datasets = [];

    ids.forEach(function(id, idx){
      var color = palette[idx % palette.length];

      // alinha dados às datas
      var ideal = align(idealG[id]||[], dates);
      var real  = align(realG[id]||[], dates);

      // se ideal do início vier nulo, mas real existir, corrige
      if(ideal[0] == null && real[0] != null) ideal[0] = real[0];

      // curva Ideal (tracejada)
      datasets.push({
        label: 'Sprint #' + id + ' — Ideal',
        data: ideal,
        borderColor: hexToRgba(color,0.65),
        borderWidth: 2,
        borderDash: [6,4],
        pointRadius: 3,
        pointBackgroundColor: hexToRgba(color,0.65),
        fill: false,
        spanGaps: true
      });

      // curva Real
      datasets.push({
        label: 'Sprint #' + id + ' — Real',
        data: real,
        borderColor: color,
        borderWidth: 3,
        pointRadius: 4,
        pointBackgroundColor: color,
        fill: false,
        spanGaps: true
      });
    });

    return datasets;
  }



  // ------------------------------------------------------
  // Renderiza ou atualiza o gráfico de burndown
  // ------------------------------------------------------
  function render(dates, datasets){
    var canvas = document.getElementById('burndownCanvas');
    if(!canvas) return;

    // define tamanho padrão
    canvas.style.width = '100%';
    canvas.style.height = '640px';
    canvas.height = 640;

    var ctx = canvas.getContext('2d');

    var displayLabels = dates.map(fmtDate);

    // cria ou atualiza gráfico existente
    if(burndownChart){
      burndownChart.data.labels = displayLabels;
      burndownChart.data.datasets = datasets;
      burndownChart.update();
      return;
    }

    // cria novo gráfico Chart.js
    burndownChart = new Chart(ctx, {
      type: 'line',
      data: { labels: displayLabels, datasets: datasets },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1,
        interaction: { mode: 'index', intersect: false },

        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              title: items => items[0].label,
              label: c => c.dataset.label + ': ' + (c.parsed.y ?? '—') + ' pts'
            }
          }
        },

        scales: {
          x: { 
            title: { display: true, text: 'Datas' },
            grid: { display: false }
          },
          y: {
            title: { display: true, text: 'Pontos restantes' },
            min: 0,
            grid: { color: 'rgba(15,23,42,0.06)' }
          }
        }
      }
    });
  }



  // ------------------------------------------------------
  // Inicializa burndown usando JSON do atributo data-response
  // ------------------------------------------------------
  function initFromResponse(res){
    if(!res || typeof res !== 'object') return;

    var idealG = group(res.ideal || []);
    var realG  = group(res.real || []);

    var idsSet = new Set(Object.keys(idealG).concat(Object.keys(realG)));
    var ids = Array.from(idsSet).sort((a,b)=>Number(a)-Number(b));

    if(!ids.length) return;

    var arrays = [].concat(
      Object.keys(idealG).map(k=>idealG[k]),
      Object.keys(realG).map(k=>realG[k])
    );

    var allDates = unionDates(arrays);
    var datasets = buildDatasets(ids, idealG, realG, allDates);

    render(allDates, datasets);
  }

  // expõe no escopo global
  window.initBurndown = initFromResponse;



  // auto-init se existir elemento #burndownData
  $(function(){
    var r = parseResponse('#burndownData');
    if(r) initFromResponse(r);
  });



})(jQuery, window, document);
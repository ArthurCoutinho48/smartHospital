// ---------------------------------------------------------
// Tempo mínimo que o loading deve permanecer exibido
// ---------------------------------------------------------
let minLoadingMs = 1200;

// Ajusta tempo mínimo (uso externo)
window.setMinLoadingMs = function(ms) {
    minLoadingMs = Math.max(0, parseInt(ms, 10) || 0);
};

// Ajusta velocidade do spinner via CSS (animation-duration)
window.setSpinnerSpeed = function(seconds) {
    let dur = (typeof seconds === "number" && seconds > 0)
        ? seconds + "s"
        : seconds;

    $(".loading-spinner").css("animation-duration", dur || "1s");
};


// ---------------------------------------------------------
// Exibe loading com mensagem customizada
// ---------------------------------------------------------
function showLoading(message) {

    $("#loadingBox p").html(
        message || "Gerando relatório com IA...<br>Por favor, aguarde."
    );

    // salva início do loading
    $("#loadingBox").data("loadingStart", Date.now());

    $("#loadingBox").fadeIn(150);
}


// ---------------------------------------------------------
// Esconde loading respeitando o tempo mínimo configurado
// ---------------------------------------------------------
function hideLoadingDeferred() {

    const start = $("#loadingBox").data("loadingStart") || 0;
    const elapsed = Date.now() - start;
    const delay = Math.max(0, minLoadingMs - elapsed);

    setTimeout(() => {
        $("#loadingBox").fadeOut(120);
    }, delay);
}


// ---------------------------------------------------------
// Esconde loading imediatamente
// ---------------------------------------------------------
function hideLoadingImmediate() {
    $("#loadingBox").stop(true, true).hide();
}


// ---------------------------------------------------------
// Escreve texto em <pre>
// ---------------------------------------------------------
function showResult(preId, text) {
    $("#" + preId).text(text);
}


// ---------------------------------------------------------
// Limpa resultados antes do envio
// ---------------------------------------------------------
function clearResults(id) {
    if (id === "1") {
        $("#saida1").text("");
    } else if (id === "2") {
        $("#saida2").text("");
    }
}



// =======================================================================
// GERAR RELATÓRIO EXECUTIVO — POST /api/generate-report
// =======================================================================
window.gerarRelatorio = function () {

    clearResults("1");
    showLoading("Gerando relatório com IA...<br>Por favor, aguarde.");

    $.ajax({
        url: "/api/generate-report",
        method: "POST",
        data: JSON.stringify({}),
        contentType: "application/json",
        processData: false,
        dataType: "text",

        success: function (text, status, xhr) {

            const ctype = xhr.getResponseHeader("content-type") || "";
            console.log("generate-report -> status:", xhr.status, "content-type:", ctype);

            // ❌ Não é JSON
            if (!ctype.includes("application/json")) {
                hideLoadingDeferred();
                showResult("saida1", "Resposta inesperada (não JSON):\n\n" + text);
                return;
            }

            // ✔ Tenta parsear JSON
            try {
                const json = JSON.parse(text);

                hideLoadingDeferred();
                showResult("saida1", json.relatorio || JSON.stringify(json, null, 2));

            } catch (error) {
                hideLoadingDeferred();
                console.error("Erro ao parsear JSON:", error, text);

                showResult(
                    "saida1",
                    "Erro ao parsear JSON:\n" + error + "\n\nResposta bruta:\n" + text
                );
            }
        },

        error: function (xhr, status, error) {
            hideLoadingDeferred();
            console.error("Erro gerarRelatorio:", error, xhr.responseText);

            showResult(
                "saida1",
                "Erro HTTP " + xhr.status + "\n\n" + xhr.responseText
            );
        }
    });
};



// =======================================================================
// GERAR LIÇÕES APRENDIDAS — POST /api/generate-lessons
// =======================================================================
window.gerarLicoes = function () {

    clearResults("2");
    showLoading("Gerando lições com IA...<br>Por favor, aguarde.");

    $.ajax({
        url: "/api/generate-lessons",
        method: "POST",
        data: JSON.stringify({}),
        contentType: "application/json",
        processData: false,
        dataType: "text",

        success: function (text, status, xhr) {

            const ctype = xhr.getResponseHeader("content-type") || "";
            console.log("generate-lessons -> status:", xhr.status, "content-type:", ctype);

            // ❌ resposta inesperada
            if (!ctype.includes("application/json")) {
                hideLoadingDeferred();
                showResult("saida2", "Resposta inesperada (não JSON):\n\n" + text);
                return;
            }

            try {
                const json = JSON.parse(text);

                hideLoadingDeferred();

                showResult(
                    "saida2",
                    json.licoes ||
                    json.licoes_aprendidas ||
                    JSON.stringify(json, null, 2)
                );

            } catch (error) {
                hideLoadingDeferred();
                console.error("Erro parse JSON:", error, text);

                showResult(
                    "saida2",
                    "Erro ao parsear JSON:\n" + error + "\n\nResposta bruta:\n" + text
                );
            }
        },

        error: function (xhr, status, error) {
            hideLoadingDeferred();
            console.error("Erro gerarLicoes:", error, xhr.responseText);

            showResult(
                "saida2",
                "Erro HTTP " + xhr.status + "\n\n" + xhr.responseText
            );
        }
    });
};

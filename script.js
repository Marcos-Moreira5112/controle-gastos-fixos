document.addEventListener("DOMContentLoaded", function () {
    
    // 1. LÓGICA DO BOTÃO MOSTRAR/ESCONDER FORMULÁRIO
    const btnToggle = document.getElementById("btn-toggle");
    const boxFormulario = document.getElementById("box-formulario");

    if (btnToggle && boxFormulario) {
        btnToggle.addEventListener("click", function () {
            if (boxFormulario.style.display === "none" || boxFormulario.style.display === "") {
                boxFormulario.style.display = "block";
                btnToggle.textContent = "✖️ Fechar Formulário";
                btnToggle.style.background = "#fff5f5";
                btnToggle.style.color = "#c53030";
            } else {
                boxFormulario.style.display = "none";
                btnToggle.textContent = "➕ Adicionar Novo Gasto";
                btnToggle.style.background = "#edf2f7";
                btnToggle.style.color = "#4a5568";
            }
        });
    }

    // 2. LÓGICA DO CHECKBOX (MARCAR COMO PAGO)
    document.body.addEventListener("change", function (e) {
        if (e.target.matches("input[type='checkbox']")) {
            const checkbox = e.target;
            const gastoId = checkbox.getAttribute("data-id");
            const itemGasto = checkbox.closest(".item-gasto");
            const novoStatus = checkbox.checked ? "Pago" : "Pendente";

            fetch("atualizar_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: gastoId, status: novoStatus })
            })
            .then(response => response.json())
            .then(dados => {
                if (dados.sucesso) {
                    if (checkbox.checked) {
                        itemGasto.classList.add("pago");
                    } else {
                        itemGasto.classList.remove("pago");
                    }
                    // Como mudou o status, damos um reload rápido para atualizar o "Falta Pagar" e "Já Pago" no topo
                    window.location.reload();
                } else {
                    alert("Erro ao atualizar o status.");
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(() => {
                alert("Erro de conexão.");
                checkbox.checked = !checkbox.checked;
            });
        }
    });

    // 3. LÓGICA DO CADASTRO VIA AJAX
    const form = document.getElementById("form-cadastro");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(form);
            const urlParams = new URLSearchParams(window.location.search);
            const mesUrl = urlParams.get('mes');

            fetch("index.php" + (mesUrl ? "?mes=" + mesUrl : ""), {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                form.reset();
                window.location.reload(); 
            });
        });
    }

    // 4. LÓGICA DO SELETOR DE HISTÓRICO MENSAL
    const seletorMes = document.getElementById("seletor-mes");
    if (seletorMes) {
        seletorMes.addEventListener("change", function () {
            const mesSelecionado = seletorMes.value;
            window.location.href = "index.php?mes=" + mesSelecionado;
        });
    }

    // 5. LÓGICA DE EXCLUSÃO VIA AJAX
    document.body.addEventListener("click", function (e) {
        if (e.target.matches(".btn-excluir")) {
            const botao = e.target;
            const gastoId = botao.getAttribute("data-id");
            const itemGasto = botao.closest(".item-gasto");

            if (confirm("Deseja realmente apagar esta conta?")) {
                fetch("excluir_gasto.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: gastoId })
                })
                .then(response => response.json())
                .then(dados => {
                    if (dados.sucesso) {
                        itemGasto.remove();
                        window.location.reload();
                    } else {
                        alert("Erro ao excluir o gasto.");
                    }
                })
                .catch(() => {
                    alert("Erro de conexão ao tentar excluir.");
                });
            }
        }
    });
});
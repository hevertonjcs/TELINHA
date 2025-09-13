// ===== FUNÇÃO DE COPIAR PIX =====
function copiarTexto(inputId, notificationId) {
  const input = document.getElementById(inputId);
  const notification = document.getElementById(notificationId);
  if (!input || !notification) return;

  navigator.clipboard.writeText(input.value).then(() => {
    notification.style.display = "block";
    setTimeout(() => { notification.style.display = "none"; }, 2000);
  }).catch(err => console.error("Erro ao copiar PIX: ", err));
}

// ===== ABRIR / FECHAR MODAL QR CODE =====
function abrirQrModal() {
  const modal = document.getElementById("qrModal");
  if (modal) modal.style.display = "flex";
}
function fecharQrModal() {
  const modal = document.getElementById("qrModal");
  if (modal) modal.style.display = "none";
}

// ===== SELEÇÃO DE VALORES RÁPIDOS =====
function setValorPix(valor, el) {
  const input = document.getElementById("pixValor");
  input.value = valor;
  document.querySelectorAll(".quick-values button").forEach(btn => btn.classList.remove("active"));
  if (el) el.classList.add("active");
}

// ===== RESET BOTÕES QUANDO DIGITA MANUAL =====
document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("pixValor");
  if (input) {
    input.addEventListener("input", () => {
      document.querySelectorAll(".quick-values button").forEach(btn => btn.classList.remove("active"));
    });
  }
});

// ===== ABRIR MODAL DE DADOS DO DOADOR COM OVERLAY =====
function gerarPixComDados() {
  const valor = window.valorDoacao || document.getElementById("pixValor").value;
  if (!valor || valor <= 0) return alert("Por favor, informe um valor válido.");

  // Oculta QR Modal se estiver aberto
  const qrModal = document.getElementById("qrModal");
  if (qrModal) qrModal.style.display = "none";

  // Pausa popups automáticos
  pausePopups();

  // Cria overlay escuro
  let overlay = document.getElementById("pixOverlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "pixOverlay";
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0,0,0,0.6)";
    overlay.style.zIndex = "9998"; // abaixo do modal do PIX
    document.body.appendChild(overlay);
  }
  overlay.style.display = "block";

  // Mostra modal do doador
  const modalDoador = document.getElementById("dadosDoadorModal");
  if (modalDoador) {
    modalDoador.style.display = "flex";
    modalDoador.style.zIndex = "9999"; // acima do overlay
  }
}

// ===== FECHAR MODAL DADOS DO DOADOR =====
function fecharDadosModal() {
  const modalDoador = document.getElementById("dadosDoadorModal");
  if (modalDoador) modalDoador.style.display = "none";

  // Esconde overlay
  const overlay = document.getElementById("pixOverlay");
  if (overlay) overlay.style.display = "none";

  // Retoma popups automáticos
  resumePopups();
}

// ===== CONFIRMAR DADOS DO DOADOR E GERAR PIX =====
function confirmarDados() {
  const nome = document.getElementById("doadorNome").value.trim();
  const doc = document.getElementById("doadorDocumento").value.trim();
  const email = document.getElementById("doadorEmail").value.trim();

  if (!nome || !doc || !email) return alert("Por favor, preencha todos os campos.");

  fecharDadosModal();
  gerarQrCodePix(window.valorDoacao, nome, doc, email);
}

// ===== GERAR QR CODE PIX =====
async function gerarQrCodePix(valor, nome, documento, email) {
  const copiaColaInput = document.getElementById("pixCopiaCola");
  const qrImg = document.getElementById("qrImagem");
  const resultado = document.getElementById("qrResultado");
  const statusMsg = document.getElementById("pixStatus");

  try {
    const resposta = await fetch("criar_pix.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ valor, nome, documento, email })
    });
    const dados = await resposta.json();

    if (dados.erro) throw new Error(dados.erro);

    const copiaCola = dados.qr_code_text ?? dados.pix?.qrCode ?? null;
    const qrBase64 = dados.qr_code_image ?? dados.pix?.qrCodeBase64 ?? null;
    const transactionId = dados.id ?? null;

    if (!copiaCola || !qrBase64) throw new Error("Dados inválidos da API");

    copiaColaInput.value = copiaCola;
    qrImg.src = qrBase64.startsWith("data:image") ? qrBase64 : "data:image/png;base64," + qrBase64;
    resultado.style.display = "block";

    if (statusMsg) {
      statusMsg.innerHTML = "⏳ Aguardando pagamento via PIX...";
      statusMsg.style.display = "block";
      statusMsg.style.color = "black";
    }

    if (transactionId) verificarStatusPix(transactionId, statusMsg);

  } catch (e) {
    console.error("Erro na API, usando fallback:", e);

    const chavePix = document.getElementById("pixKey1")?.value || "pix@orfanatoirmadulce.com";
    const payload = `00020126580014BR.GOV.BCB.PIX0114${chavePix}520400005303986540${valor}5802BR5925Orfanato Irma Dulce6009Sao Paulo62070503***6304ABCD`;

    copiaColaInput.value = payload;
    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(payload)}&size=250x250`;
    resultado.style.display = "block";

    if (statusMsg) {
      statusMsg.innerHTML = "⏳ Aguardando pagamento via PIX (fallback)...";
      statusMsg.style.display = "block";
      statusMsg.style.color = "black";
    }
  }
}

// ===== VERIFICAÇÃO DE STATUS PIX =====
async function verificarStatusPix(transactionId, statusMsg) {
  try {
    const resposta = await fetch("status_pix.php?id=" + transactionId);
    const dados = await resposta.json();

    if (dados.status === "approved" || dados.status === "paid") {
      if (statusMsg) { statusMsg.innerHTML = "✅ Obrigado por contribuir!"; statusMsg.style.color = "green"; }
      return;
    }

    setTimeout(() => verificarStatusPix(transactionId, statusMsg), 5000);
  } catch (e) {
    console.error("Erro ao verificar status PIX:", e);
    setTimeout(() => verificarStatusPix(transactionId, statusMsg), 5000);
  }
}

// ===== POPUPS =====
const donationPopup = document.getElementById("donationPopup");
const rosaryPopup = document.getElementById("rosaryPopup");
const closeDonation = document.getElementById("closePopup");
const closeRosary = document.getElementById("closeRosaryPopup");

let rosaryTimer = setTimeout(() => { if (rosaryPopup) rosaryPopup.style.display = "flex"; }, 15000);
let donationTimer = setTimeout(() => { if (donationPopup) donationPopup.style.display = "flex"; }, 30000);

// ===== FUNÇÕES AUXILIARES =====
function pausePopups() {
  [rosaryPopup, donationPopup].forEach(p => { if (p) p.style.display = "none"; });
  clearTimeout(rosaryTimer);
  clearTimeout(donationTimer);
}

function resumePopups() {
  rosaryTimer = setTimeout(() => { if (rosaryPopup) rosaryPopup.style.display = "flex"; }, 15000);
  donationTimer = setTimeout(() => { if (donationPopup) donationPopup.style.display = "flex"; }, 30000);
}

// ===== EVENTOS DE FECHAR POPUPS =====
function fecharPopup(popup, timer) {
  if (popup) popup.style.display = "none";
  if (timer) clearTimeout(timer);
}
if (closeRosary) closeRosary.addEventListener("click", () => fecharPopup(rosaryPopup, rosaryTimer));
if (rosaryPopup) rosaryPopup.addEventListener("click", (e) => { if (e.target === rosaryPopup) fecharPopup(rosaryPopup, rosaryTimer); });
if (closeDonation) closeDonation.addEventListener("click", () => fecharPopup(donationPopup, donationTimer));
if (donationPopup) donationPopup.addEventListener("click", (e) => { if (e.target === donationPopup) fecharPopup(donationPopup, donationTimer); });

// ===== FAQ =====
document.querySelectorAll(".faq-item").forEach((item) => { item.addEventListener("click", () => item.classList.toggle("active")); });



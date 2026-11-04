// Calendário do <x-date-picker>: troca os <input type="date"> nativos por um botão que
// abre um mês navegável. Dias de pregão ganham um ponto; datas fora de [min, max] ficam
// desabilitadas. Modo single: clicar no dia já envia o form. Modo range: 1º clique marca
// o início, 2º o fim (invertidos se vierem ao contrário) e "Aplicar" envia.
// Datas trafegam sempre como 'YYYY-MM-DD' (sem fuso: nada de Date#toISOString).

const pad = (n) => String(n).padStart(2, '0');
const iso = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
const partes = (s) => s.split('-').map(Number);
const br = (s) => { const [y, m, d] = partes(s); return `${pad(d)}/${pad(m)}/${y}`; };
const somarDias = (s, n) => { const [y, m, d] = partes(s); const t = new Date(y, m - 1, d + n); return iso(t.getFullYear(), t.getMonth(), t.getDate()); };
const hoje = () => { const t = new Date(); return iso(t.getFullYear(), t.getMonth(), t.getDate()); };

const fmtMes = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
const SEMANA = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

function montar(form) {
  const range = form.dataset.mode === 'range';
  const min = form.dataset.min || null;
  const max = form.dataset.max || hoje();
  const pregoes = new Set(JSON.parse(form.dataset.pregoes || '[]'));
  const trigger = form.querySelector('[data-datepicker-trigger]');
  const texto = form.querySelector('[data-datepicker-text]');
  const pop = form.querySelector('[data-datepicker-pop]');
  const inputs = range
    ? [form.querySelector('input[name="de"]'), form.querySelector('input[name="ate"]')]
    : [form.querySelector('input[name="data"]')];

  // Progressive enhancement: esconde o fallback nativo e mostra o botão
  form.querySelector('.datepicker__fallback').hidden = true;
  inputs.forEach((i) => { i.type = 'hidden'; });
  trigger.hidden = false;

  const dentro = (s) => (!min || s >= min) && s <= max;
  let inicio = inputs[0].value || null;
  let fim = range ? (inputs[1].value || null) : null;
  let foco = inicio || max;
  let [anoVis, mesVis] = partes(foco); mesVis -= 1;

  const fechar = (devolverFoco = true) => {
    pop.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    document.removeEventListener('pointerdown', foraDoPop, true);
    if (devolverFoco) trigger.focus();
  };
  const foraDoPop = (e) => { if (!form.contains(e.target)) fechar(false); };

  const enviar = () => {
    inputs[0].value = inicio;
    if (range) inputs[1].value = fim;
    texto.textContent = range ? `${br(inicio)} – ${br(fim)}` : br(inicio);
    fechar(false);
    form.submit();
  };

  function escolher(s) {
    if (!range) { inicio = s; enviar(); return; }
    if (!inicio || fim) { inicio = s; fim = null; }
    else if (s < inicio) { fim = inicio; inicio = s; }
    else { fim = s; }
    foco = s;
    render();
  }

  function render() {
    const primeiro = new Date(anoVis, mesVis, 1);
    const offset = primeiro.getDay();
    const diasNoMes = new Date(anoVis, mesVis + 1, 0).getDate();
    const ultimoDoMes = iso(anoVis, mesVis, diasNoMes);
    const primeiroDoMes = iso(anoVis, mesVis, 1);

    pop.innerHTML = '';

    const head = document.createElement('div');
    head.className = 'datepicker__head';
    const prev = botaoNav('Mês anterior', 'M10 3L5 8l5 5', () => mudarMes(-1));
    const next = botaoNav('Próximo mês', 'M6 3l5 5-5 5', () => mudarMes(1));
    prev.disabled = !!min && primeiroDoMes <= min;
    next.disabled = ultimoDoMes >= max;
    const titulo = document.createElement('span');
    titulo.className = 'datepicker__title';
    const mes = fmtMes.format(primeiro);
    titulo.textContent = mes.charAt(0).toUpperCase() + mes.slice(1);
    titulo.setAttribute('aria-live', 'polite');
    head.append(prev, titulo, next);

    const grid = document.createElement('div');
    grid.className = 'datepicker__grid';
    grid.setAttribute('role', 'grid');
    SEMANA.forEach((d) => {
      const w = document.createElement('span');
      w.className = 'datepicker__wd';
      w.textContent = d;
      w.setAttribute('aria-hidden', 'true');
      grid.append(w);
    });
    for (let i = 0; i < offset; i++) grid.append(document.createElement('span'));

    for (let d = 1; d <= diasNoMes; d++) {
      const s = iso(anoVis, mesVis, d);
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'datepicker__day';
      b.textContent = d;
      b.dataset.date = s;
      b.disabled = !dentro(s);
      b.tabIndex = s === foco ? 0 : -1;
      const pregao = pregoes.has(s);
      b.classList.toggle('is-pregao', pregao);
      b.classList.toggle('is-today', s === hoje());
      const sel = s === inicio || s === fim;
      b.classList.toggle('is-selected', sel);
      b.classList.toggle('is-in-range', range && inicio && fim && s > inicio && s < fim);
      b.setAttribute('aria-pressed', String(sel));
      b.setAttribute('aria-label', `${br(s)}${pregao ? ', dia de pregão' : ''}`);
      b.addEventListener('click', () => escolher(s));
      grid.append(b);
    }
    grid.addEventListener('keydown', teclado);

    const foot = document.createElement('div');
    foot.className = 'datepicker__foot';
    const legenda = document.createElement('span');
    legenda.className = 'datepicker__legend';
    if (!range) legenda.innerHTML = '<span class="datepicker__dot" aria-hidden="true"></span>';
    legenda.append(document.createTextNode(range ? dicaRange() : 'Dia com pregão'));
    foot.append(legenda);

    if (range) {
      const aplicar = document.createElement('button');
      aplicar.type = 'button';
      aplicar.className = 'btn btn-primary btn--sm';
      aplicar.textContent = 'Aplicar';
      aplicar.disabled = !(inicio && fim);
      aplicar.addEventListener('click', enviar);
      foot.append(aplicar);
    }

    pop.append(head, grid, foot);
  }

  const dicaRange = () => (!inicio ? 'Escolha o início' : !fim ? `De ${br(inicio)} — escolha o fim` : `${br(inicio)} – ${br(fim)}`);

  function botaoNav(rotulo, d, onClick) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'icon-btn icon-btn--bare datepicker__nav';
    b.setAttribute('aria-label', rotulo);
    b.innerHTML = `<svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path d="${d}" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    b.addEventListener('click', onClick);
    return b;
  }

  function mudarMes(delta) {
    const t = new Date(anoVis, mesVis + delta, 1);
    anoVis = t.getFullYear();
    mesVis = t.getMonth();
    const [fy, fm] = partes(foco);
    if (fy !== anoVis || fm - 1 !== mesVis) foco = iso(anoVis, mesVis, 1);
    render();
  }

  // Setas movem o foco (dia/semana), PageUp/PageDown trocam o mês, Esc fecha
  function teclado(e) {
    const passos = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
    if (e.key in passos || e.key === 'PageUp' || e.key === 'PageDown') {
      e.preventDefault();
      let alvo = e.key in passos ? somarDias(foco, passos[e.key]) : somarDias(foco, e.key === 'PageUp' ? -30 : 30);
      if (max && alvo > max) alvo = max;
      if (min && alvo < min) alvo = min;
      foco = alvo;
      [anoVis, mesVis] = partes(foco); mesVis -= 1;
      render();
      pop.querySelector(`[data-date="${foco}"]`)?.focus();
    }
  }

  // Em telas estreitas o popover (ancorado no botão) pode passar da borda: desliza para dentro
  function caberNaTela() {
    pop.style.left = '';
    pop.style.right = '';
    const margem = 12;
    const r = pop.getBoundingClientRect();
    const base = form.getBoundingClientRect().left;
    // clientWidth, não innerWidth: no celular o innerWidth cresce junto com o próprio estouro
    const esquerda = Math.min(Math.max(r.left, margem), document.documentElement.clientWidth - margem - r.width);
    if (esquerda !== r.left) {
      pop.style.left = `${esquerda - base}px`;
      pop.style.right = 'auto';
    }
  }

  trigger.addEventListener('click', () => {
    if (!pop.hidden) { fechar(); return; }
    inicio = inputs[0].value || null;
    fim = range ? (inputs[1].value || null) : null;
    foco = inicio && dentro(inicio) ? inicio : max;
    [anoVis, mesVis] = partes(foco); mesVis -= 1;
    render();
    pop.hidden = false;
    caberNaTela();
    trigger.setAttribute('aria-expanded', 'true');
    document.addEventListener('pointerdown', foraDoPop, true);
    pop.querySelector(`[data-date="${foco}"]`)?.focus();
  });
  form.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !pop.hidden) { e.stopPropagation(); fechar(); } });
}

document.querySelectorAll('[data-datepicker]').forEach(montar);

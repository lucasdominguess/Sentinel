// acoes-show.js — gráfico de evolução do preço (SVG vanilla, cores via CSS/tema)
// Fonte: fechamento diário (cotacoes_diarias), não o snapshot intradiário —
// um ponto por dia de pregão é o que torna a série comparável ao longo do tempo.
const el = document.getElementById('chart');
const empty = document.getElementById('chart-empty');
const rangeLabel = document.getElementById('chart-range');

const SVG_NS = 'http://www.w3.org/2000/svg';
const W = 820;
const H = 320;
const PAD = { top: 20, right: 16, bottom: 34, left: 60 };
const DIAS_NO_ANO = 365;

if (el) {
    const endpoint = el.dataset.endpoint;
    fetch(`${endpoint}?perPage=500`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((payload) => render(payload?.data?.items ?? []))
        .catch(() => showEmpty());
}

function showEmpty() {
    if (empty) empty.hidden = false;
    if (el) el.hidden = true;
}

function node(name, attrs = {}) {
    const n = document.createElementNS(SVG_NS, name);
    for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
    return n;
}

function fmtPreco(v) {
    return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// 'YYYY-MM-DD' no fuso local: new Date(iso) assumiria UTC e exibiria o dia anterior
function parseData(iso) {
    if (!iso) return null;
    const [ano, mes, dia] = iso.split('-').map(Number);
    return new Date(ano, mes - 1, dia);
}

function fmtData(d, comAno = false) {
    return comAno
        ? d.toLocaleDateString('pt-BR', { month: '2-digit', year: '2-digit' })
        : d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

function render(items) {
    const pts = items
        .map((c) => ({ t: parseData(c.data), v: parseFloat(c.fechamento) }))
        .filter((p) => p.t && !Number.isNaN(p.v) && !Number.isNaN(p.t.getTime()));

    if (pts.length < 2) {
        showEmpty();
        return;
    }

    const vals = pts.map((p) => p.v);
    let min = Math.min(...vals);
    let max = Math.max(...vals);
    if (min === max) {
        min -= 1;
        max += 1;
    }
    const padV = (max - min) * 0.12;
    min -= padV;
    max += padV;

    const innerW = W - PAD.left - PAD.right;
    const innerH = H - PAD.top - PAD.bottom;
    const x = (i) => PAD.left + (i * innerW) / (pts.length - 1);
    const y = (v) => PAD.top + innerH - ((v - min) * innerH) / (max - min);

    const svg = node('svg', { viewBox: `0 0 ${W} ${H}`, class: 'chart-svg', role: 'img' });

    // gridlines + labels do eixo Y (min, meio, max)
    [0, 0.5, 1].forEach((f) => {
        const val = min + (max - min) * f;
        const gy = y(val);
        svg.appendChild(node('line', { x1: PAD.left, y1: gy, x2: W - PAD.right, y2: gy, class: 'chart-grid' }));
        const label = node('text', { x: PAD.left - 8, y: gy + 4, class: 'chart-label chart-label--y' });
        label.textContent = fmtPreco(val);
        svg.appendChild(label);
    });

    const linePoints = pts.map((p, i) => `${x(i)},${y(p.v)}`).join(' ');
    const areaPoints = `${x(0)},${y(min)} ${linePoints} ${x(pts.length - 1)},${y(min)}`;

    svg.appendChild(node('polygon', { points: areaPoints, class: 'chart-area' }));
    svg.appendChild(node('polyline', { points: linePoints, class: 'chart-line' }));

    const last = pts[pts.length - 1];
    svg.appendChild(node('circle', { cx: x(pts.length - 1), cy: y(last.v), r: 4, class: 'chart-dot' }));

    // Série de mais de um ano cabe melhor rotulada por mês/ano
    const dias = (last.t - pts[0].t) / 86400000;
    const comAno = dias > DIAS_NO_ANO;

    // labels do eixo X (primeira e última data)
    const firstLbl = node('text', { x: PAD.left, y: H - 10, class: 'chart-label' });
    firstLbl.textContent = fmtData(pts[0].t, comAno);
    svg.appendChild(firstLbl);
    const lastLbl = node('text', { x: W - PAD.right, y: H - 10, class: 'chart-label chart-label--end' });
    lastLbl.textContent = fmtData(last.t, comAno);
    svg.appendChild(lastLbl);

    el.appendChild(svg);

    if (rangeLabel) {
        rangeLabel.textContent = `${pts.length} dias · ${fmtData(pts[0].t, comAno)} → ${fmtData(last.t, comAno)}`;
    }
}

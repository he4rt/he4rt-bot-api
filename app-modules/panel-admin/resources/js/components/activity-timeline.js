/**
 * Linha do tempo de atividade da comunidade.
 *
 * Seis faixas empilhadas sobre um único eixo X: régua de mês/dia, três trilhas
 * de calor, um streamgraph do GitHub por tipo e duas áreas do Discord. Toda a
 * geometria é função pura dos dias — o Alpine só guarda seleção, camadas e foco.
 *
 * As cores saem de custom properties (`var(--tl-*)`), nunca de strings resolvidas,
 * senão o SVG congela o tema do primeiro paint quando o painel troca claro/escuro.
 */

const NS = 'http://www.w3.org/2000/svg';

const TL = { W: 1000, L: 86, R: 40, gap: 7 };

const Y = {
    month: 12,
    wd: 26,
    dn: 38,
    t0: 46,
    th: 20,
    tg: 4,
    stream: [128, 228],
    msg: [242, 300],
    voice: [314, 372],
    H: 382,
};

const RAMP6 = ['--tl-t0', '--tl-t1', '--tl-t2', '--tl-t3', '--tl-t4', '--tl-t5'];
const LV = ['--tl-l0', '--tl-l1', '--tl-l2', '--tl-l3', '--tl-l4'];

const WD = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
const WDL = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
const MON = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

const NF = new Intl.NumberFormat('pt-BR');
const fmt = (n) => NF.format(Math.round(n));
const esc = (v) =>
    String(v == null ? '' : v).replace(
        /[&<>"']/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
    );

const parseDate = (iso) => {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
};
const shortDate = (iso) => {
    const d = parseDate(iso);
    return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
};
const longDate = (iso) => WD[parseDate(iso).getDay()] + ' ' + shortDate(iso);

const v = (token) => `var(${token})`;
const sum = (arr, f) => arr.reduce((t, x) => t + (f(x) || 0), 0);

function el(tag, attrs, ...kids) {
    const node = document.createElementNS(NS, tag);
    if (attrs) {
        for (const k in attrs) {
            if (k === 'text') {
                node.textContent = attrs[k];
            } else {
                node.setAttribute(k, attrs[k]);
            }
        }
    }
    kids.filter(Boolean).forEach((k) => node.append(k));
    return node;
}

/** Quartis dos dias com dado e valor positivo: a escala de calor acompanha o período. */
function quantileLevels(values) {
    const sorted = values.filter((x) => x > 0).sort((a, b) => a - b);
    if (!sorted.length) {
        return [1, 2, 3];
    }
    const at = (p) => sorted[Math.min(sorted.length - 1, Math.floor(p * (sorted.length - 1)))];
    return [at(0.25), at(0.5), at(0.75)];
}

const levelOf = (x, th) => (x <= 0 ? 0 : x <= th[0] ? 1 : x <= th[1] ? 2 : x <= th[2] ? 3 : 4);

/** Fritsch–Carlson: tangentes que não deixam a curva ultrapassar os pontos. */
function monotoneTangents(xs, ys) {
    const n = xs.length;
    const m = new Array(n).fill(0);
    if (n < 2) {
        return m;
    }
    const delta = [];
    for (let i = 0; i < n - 1; i++) {
        delta.push((ys[i + 1] - ys[i]) / (xs[i + 1] - xs[i] || 1e-6));
    }
    m[0] = delta[0];
    m[n - 1] = delta[n - 2];
    for (let i = 1; i < n - 1; i++) {
        m[i] = delta[i - 1] * delta[i] <= 0 ? 0 : (delta[i - 1] + delta[i]) / 2;
    }
    for (let i = 0; i < n - 1; i++) {
        if (delta[i] === 0) {
            m[i] = 0;
            m[i + 1] = 0;
            continue;
        }
        const a = m[i] / delta[i];
        const b = m[i + 1] / delta[i];
        const s2 = a * a + b * b;
        if (s2 > 9) {
            const t = 3 / Math.sqrt(s2);
            m[i] = t * a * delta[i];
            m[i + 1] = t * b * delta[i];
        }
    }
    return m;
}

function curveSegments(xs, ys, reverse) {
    const n = xs.length;
    if (n === 1) {
        return `L${xs[0]} ${ys[0]}`;
    }
    const X = reverse ? xs.slice().reverse() : xs;
    const V = reverse ? ys.slice().reverse() : ys;
    const m = monotoneTangents(
        X.map((x) => (reverse ? -x : x)),
        V,
    ).map((t) => (reverse ? -t : t));
    let d = '';
    for (let i = 0; i < n - 1; i++) {
        const step = (X[i + 1] - X[i]) / 3;
        d +=
            `C${(X[i] + step).toFixed(2)} ${(V[i] + m[i] * step).toFixed(2)} ` +
            `${(X[i + 1] - step).toFixed(2)} ${(V[i + 1] - m[i + 1] * step).toFixed(2)} ` +
            `${X[i + 1].toFixed(2)} ${V[i + 1].toFixed(2)}`;
    }
    return d;
}

const linePath = (xs, ys) => `M${xs[0].toFixed(2)} ${ys[0].toFixed(2)}` + curveSegments(xs, ys, false);

const areaPath = (xs, top, bottom) =>
    `M${xs[0].toFixed(2)} ${top[0].toFixed(2)}` +
    curveSegments(xs, top, false) +
    `L${xs[xs.length - 1].toFixed(2)} ${bottom[bottom.length - 1].toFixed(2)}` +
    curveSegments(xs, bottom, true) +
    'Z';

export default function activityTimeline({ days: rawDays, meta, types: rawTypes }) {
    return {
        meta,
        days: [],
        types: [],
        sel: null,
        anchor: null,
        focus: 0,
        layers: { github: true, discord: true },
        tip: { open: false, html: '' },
        hoverType: null,
        lastDataIndex: -1,
        th: { gh: [1, 2, 3], ms: [1, 2, 3], vc: [1, 2, 3] },
        weekIndex: [],
        cw: 0,
        svg: null,
        overlay: null,
        crosshair: null,
        hits: [],
        layerPaths: [],

        init() {
            this.days = rawDays.map((row, i) => {
                const date = parseDate(row.date);

                return {
                    i,
                    date: row.date,
                    wd: date.getDay(),
                    dn: date.getDate(),
                    mo: date.getMonth(),
                    // Zero e lacuna são coisas diferentes: sem coleta vira hachura, não vale.
                    has: row.date <= this.meta.dataUntil,
                    gh: row.gh,
                    ms: row.ms,
                    vc: row.vc,
                };
            });

            // O tipo mais frequente recebe o tom mais fechado da rampa.
            this.types = rawTypes
                .slice()
                .sort((a, b) => b.count - a.count)
                .map((t, i) => ({ ...t, color: v(RAMP6[i]) }));

            const withData = this.days.filter((d) => d.has);
            this.lastDataIndex = withData.length ? withData[withData.length - 1].i : -1;

            this.th = {
                gh: quantileLevels(withData.map((d) => d.gh.total)),
                ms: quantileLevels(withData.map((d) => d.ms.messages)),
                vc: quantileLevels(withData.map((d) => d.vc.sessions)),
            };

            this.geometry();
            this.build();
            this.paintSelection();
        },

        /** Calha de 7px entre semanas ISO: é o que faz o heatmap ler como calendário. */
        geometry() {
            this.weekIndex = [];
            let week = 0;
            this.days.forEach((d, i) => {
                if (i > 0 && d.wd === 0) {
                    week++;
                }
                this.weekIndex.push(week);
            });

            const weeks = (this.weekIndex[this.weekIndex.length - 1] ?? 0) + 1;
            this.cw = (TL.W - TL.L - TL.R - TL.gap * (weeks - 1)) / this.days.length;
        },

        dx(i) {
            return TL.L + i * this.cw + this.weekIndex[i] * TL.gap;
        },

        dcx(i) {
            return this.dx(i) + this.cw / 2;
        },

        get selectedDays() {
            if (!this.sel) {
                return this.days.filter((d) => d.has);
            }
            const [a, b] = [Math.min(this.sel.from, this.sel.to), Math.max(this.sel.from, this.sel.to)];

            return this.days.filter((d) => d.i >= a && d.i <= b);
        },

        get selectionLabel() {
            if (!this.sel) {
                return 'período completo';
            }
            const a = Math.min(this.sel.from, this.sel.to);
            const b = Math.max(this.sel.from, this.sel.to);

            return a === b
                ? longDate(this.days[a].date)
                : `${shortDate(this.days[a].date)} → ${shortDate(this.days[b].date)} · ${b - a + 1} dias`;
        },

        build() {
            const wrap = this.$refs.canvas;
            wrap.innerHTML = '';

            const svg = el('svg', {
                viewBox: `0 0 ${TL.W} ${Y.H}`,
                role: 'img',
                'aria-label': 'Linha do tempo com heatmap por dia e séries diárias',
            });
            this.svg = svg;

            svg.append(this.defs());
            svg.append(this.ruler());
            this.tracks().forEach((g) => svg.append(g));
            svg.append(this.stream());
            svg.append(
                this.area({
                    y: Y.msg,
                    label: 'MENSAGENS',
                    sub: 'POR DIA',
                    value: (d) => d.ms.messages,
                    layer: 'discord',
                }),
            );
            svg.append(
                this.area({
                    y: Y.voice,
                    label: 'VOZ',
                    sub: 'SESSÕES/DIA',
                    value: (d) => d.vc.sessions,
                    layer: 'discord',
                }),
            );

            const gap = this.noDataOverlay();
            if (gap) {
                svg.append(gap);
            }

            this.overlay = el('g', { 'pointer-events': 'none' });
            svg.append(this.overlay);

            this.crosshair = el(
                'g',
                { 'pointer-events': 'none', opacity: 0 },
                el('line', {
                    x1: 0,
                    y1: Y.t0 - 2,
                    x2: 0,
                    y2: Y.H - 4,
                    stroke: v('--tl-acc3'),
                    'stroke-width': 1,
                    'stroke-dasharray': '3 3',
                }),
            );
            svg.append(this.crosshair);

            svg.append(this.hitAreas());
            this.bindBrush(svg);

            wrap.append(svg);
            this.applyLayers();
        },

        defs() {
            const defs = el('defs');

            defs.append(
                el(
                    'pattern',
                    {
                        id: this.$id('hatch'),
                        width: 6,
                        height: 6,
                        patternUnits: 'userSpaceOnUse',
                        patternTransform: 'rotate(45)',
                    },
                    el('rect', { width: 6, height: 6, fill: v('--tl-bg1') }),
                    el('line', { x1: 0, y1: 0, x2: 0, y2: 6, stroke: v('--tl-line2'), 'stroke-width': 1.2 }),
                ),
            );

            defs.append(
                el(
                    'linearGradient',
                    { id: this.$id('area'), x1: 0, y1: 0, x2: 0, y2: 1 },
                    el('stop', { offset: '0%', 'stop-color': v('--tl-acc'), 'stop-opacity': 0.55 }),
                    el('stop', { offset: '100%', 'stop-color': v('--tl-acc'), 'stop-opacity': 0.04 }),
                ),
            );

            return defs;
        },

        ruler() {
            const g = el('g');
            let month = -1;

            this.days.forEach((d, i) => {
                if (d.mo !== month) {
                    month = d.mo;
                    const x = this.dx(i);
                    g.append(
                        el('text', {
                            x,
                            y: Y.month,
                            fill: v('--tl-ink2'),
                            'font-size': 10,
                            'font-weight': 600,
                            'letter-spacing': '.14em',
                            text: MON[d.mo].toUpperCase(),
                        }),
                    );
                    if (i > 0) {
                        g.append(
                            el('line', {
                                x1: x - TL.gap / 2,
                                y1: 4,
                                x2: x - TL.gap / 2,
                                y2: Y.H - 6,
                                stroke: v('--tl-line2'),
                                'stroke-dasharray': '2 3',
                            }),
                        );
                    }
                }

                g.append(
                    el('text', {
                        x: this.dcx(i),
                        y: Y.wd,
                        'text-anchor': 'middle',
                        fill: d.wd === 0 || d.wd === 6 ? v('--tl-ink3') : v('--tl-ink2'),
                        'font-size': 9,
                        class: 'tl-mono',
                        text: WDL[d.wd],
                    }),
                );

                g.append(
                    el('text', {
                        x: this.dcx(i),
                        y: Y.dn,
                        'text-anchor': 'middle',
                        fill: v('--tl-ink3'),
                        'font-size': 9,
                        class: 'tl-mono',
                        text: String(d.dn).padStart(2, '0'),
                    }),
                );
            });

            return g;
        },

        tracks() {
            const defs = [
                { label: 'GITHUB', value: (d) => d.gh.total, th: () => this.th.gh, layer: 'github' },
                { label: 'MENSAGENS', value: (d) => d.ms.messages, th: () => this.th.ms, layer: 'discord' },
                { label: 'VOZ', value: (d) => d.vc.sessions, th: () => this.th.vc, layer: 'discord' },
            ];

            return defs.map((track, ti) => {
                const y = Y.t0 + ti * (Y.th + Y.tg);
                const g = el('g', { 'data-layer': track.layer });

                g.append(
                    el('text', {
                        x: TL.L - 8,
                        y: y + Y.th / 2 + 3,
                        'text-anchor': 'end',
                        fill: v('--tl-ink2'),
                        'font-size': 9,
                        'font-weight': 600,
                        'letter-spacing': '.12em',
                        text: track.label,
                    }),
                );

                this.days.forEach((d, i) => {
                    const level = d.has ? levelOf(track.value(d), track.th()) : -1;
                    g.append(
                        el('rect', {
                            x: this.dx(i) + 1.5,
                            y,
                            width: this.cw - 3,
                            height: Y.th,
                            rx: 3,
                            fill: level < 0 ? `url(#${this.$id('hatch')})` : v(LV[level]),
                            stroke: level < 0 ? v('--tl-line2') : 'none',
                            'stroke-width': 0.6,
                        }),
                    );
                });

                return g;
            });
        },

        stream() {
            const g = el('g', { 'data-layer': 'github' });
            const idx = this.days.slice(0, this.lastDataIndex + 1).map((d) => d.i);

            if (!idx.length) {
                return g;
            }

            const xs = idx.map((i) => this.dcx(i));
            const totals = idx.map((i) => sum(this.types, (t) => this.days[i].gh[t.key]));
            const max = Math.max(1, ...totals);
            const [y0, y1] = Y.stream;
            const height = y1 - y0;
            const scale = (val) => (val / max) * (height - 6);

            [
                { y: y0 + 10, size: 9, weight: 600, fill: '--tl-ink2', text: 'GITHUB' },
                { y: y0 + 22, size: 8, weight: 400, fill: '--tl-ink3', text: 'POR TIPO' },
                { y: y0 + 34, size: 8, weight: 400, fill: '--tl-ink3', text: `máx ${fmt(max)}/dia` },
            ].forEach((line) => {
                g.append(
                    el('text', {
                        x: TL.L - 8,
                        y: line.y,
                        'text-anchor': 'end',
                        fill: v(line.fill),
                        'font-size': line.size,
                        'font-weight': line.weight,
                        'letter-spacing': '.1em',
                        text: line.text,
                    }),
                );
            });

            // Silhueta central: a pilha cresce para cima a partir da metade da faixa.
            const middle = (y0 + y1) / 2;
            let bottom = totals.map((t) => middle + scale(t) / 2);

            this.layerPaths = [];
            const labels = el('g', { 'pointer-events': 'none' });

            this.types.forEach((type, li) => {
                const top = bottom.map((b, k) => b - scale(this.days[idx[k]].gh[type.key]));
                const path = el('path', {
                    class: 'tl-layer',
                    d: areaPath(xs, top, bottom),
                    fill: type.color,
                    stroke: v('--tl-bg1'),
                    'stroke-width': 1,
                });
                path.append(el('title', { text: type.label }));

                path.addEventListener('pointerenter', () => {
                    this.hoverType = type.key;
                    this.layerPaths.forEach((p) => (p.style.opacity = p === path ? 1 : 0.18));
                });
                path.addEventListener('pointerleave', () => {
                    this.hoverType = null;
                    this.layerPaths.forEach((p) => (p.style.opacity = ''));
                });

                g.append(path);
                this.layerPaths.push(path);

                let thickest = 0;
                let width = 0;
                top.forEach((t, k) => {
                    if (bottom[k] - t > width) {
                        width = bottom[k] - t;
                        thickest = k;
                    }
                });

                if (width >= 12) {
                    labels.append(
                        el('text', {
                            class: 'tl-layer-label',
                            x: Math.max(xs[0] + 26, Math.min(xs[xs.length - 1] - 26, xs[thickest])),
                            y: (top[thickest] + bottom[thickest]) / 2 + 3,
                            'text-anchor': 'middle',
                            fill: li < 3 ? v('--tl-on-dark') : v('--tl-on-light'),
                            text: type.label,
                        }),
                    );
                }

                bottom = top;
            });

            g.append(labels);

            this.types.forEach((type, k) => {
                const x = this.dx(0) + k * 118;
                g.append(el('rect', { x, y: y1 + 4, width: 8, height: 8, rx: 2, fill: type.color }));
                g.append(el('text', { x: x + 12, y: y1 + 11, fill: v('--tl-ink3'), 'font-size': 9, text: type.label }));
                g.append(
                    el('text', {
                        x: x + 12 + type.label.length * 5 + 4,
                        y: y1 + 11,
                        fill: v('--tl-ink2'),
                        'font-size': 9,
                        class: 'tl-mono',
                        text: fmt(type.count),
                    }),
                );
            });

            return g;
        },

        area(cfg) {
            const g = el('g', { 'data-layer': cfg.layer });
            const idx = this.days.slice(0, this.lastDataIndex + 1).map((d) => d.i);

            if (!idx.length) {
                return g;
            }

            const [y0, y1] = cfg.y;
            const height = y1 - y0;
            const xs = idx.map((i) => this.dcx(i));
            const values = idx.map((i) => cfg.value(this.days[i]));
            const max = Math.max(1, ...values);
            const ys = values.map((val) => y1 - (val / max) * (height - 8));

            [
                { y: y0 + 10, size: 9, weight: 600, fill: '--tl-ink2', text: cfg.label },
                { y: y0 + 22, size: 8, weight: 400, fill: '--tl-ink3', text: cfg.sub },
                { y: y0 + 34, size: 8, weight: 400, fill: '--tl-ink3', text: `máx ${fmt(max)}` },
            ].forEach((line) => {
                g.append(
                    el('text', {
                        x: TL.L - 8,
                        y: line.y,
                        'text-anchor': 'end',
                        fill: v(line.fill),
                        'font-size': line.size,
                        'font-weight': line.weight,
                        'letter-spacing': '.1em',
                        text: line.text,
                    }),
                );
            });

            const right = this.dx(this.days.length - 1) + this.cw;

            [0.5, 1].forEach((f) => {
                const y = y1 - f * (height - 8);
                g.append(
                    el('line', { x1: this.dx(0), y1: y, x2: right, y2: y, stroke: v('--tl-line'), 'stroke-width': 1 }),
                );
                g.append(
                    el('text', {
                        x: right + 3,
                        y: y + 3,
                        fill: v('--tl-ink3'),
                        'font-size': 7.5,
                        class: 'tl-mono',
                        text: fmt(max * f),
                    }),
                );
            });

            g.append(el('line', { x1: this.dx(0), y1, x2: right, y2: y1, stroke: v('--tl-line2'), 'stroke-width': 1 }));
            g.append(
                el('path', {
                    d: areaPath(
                        xs,
                        ys,
                        ys.map(() => y1),
                    ),
                    fill: `url(#${this.$id('area')})`,
                }),
            );
            g.append(el('path', { d: linePath(xs, ys), fill: 'none', stroke: v('--tl-acc2'), 'stroke-width': 1.6 }));

            const peak = values.indexOf(max);
            const late = peak > idx.length * 0.8;
            g.append(
                el('circle', {
                    cx: xs[peak],
                    cy: ys[peak],
                    r: 3,
                    fill: v('--tl-acc3'),
                    stroke: v('--tl-bg1'),
                    'stroke-width': 1.5,
                }),
            );
            g.append(
                el('text', {
                    x: late ? xs[peak] - 6 : xs[peak] + 6,
                    y: ys[peak] + 3,
                    'text-anchor': late ? 'end' : 'start',
                    fill: v('--tl-acc3'),
                    'font-size': 9,
                    class: 'tl-mono',
                    text: `${fmt(max)} · ${shortDate(this.days[idx[peak]].date)}`,
                }),
            );

            return g;
        },

        noDataOverlay() {
            if (this.lastDataIndex >= this.days.length - 1) {
                return null;
            }

            const from = this.lastDataIndex + 1;
            const x0 = this.dx(from) - TL.gap / 2;
            const x1 = this.dx(this.days.length - 1) + this.cw;
            const midY = (Y.stream[0] + Y.voice[1]) / 2;
            const g = el('g');

            g.append(
                el('rect', {
                    x: x0,
                    y: Y.stream[0] - 2,
                    width: x1 - x0,
                    height: Y.H - Y.stream[0] - 4,
                    fill: `url(#${this.$id('hatch')})`,
                    opacity: 0.9,
                    rx: 3,
                }),
            );

            g.append(
                el('text', {
                    x: (x0 + x1) / 2,
                    y: midY - 6,
                    'text-anchor': 'middle',
                    fill: v('--tl-ink3'),
                    'font-size': 9,
                    'font-weight': 600,
                    'letter-spacing': '.12em',
                    text: 'SEM COLETA',
                }),
            );

            g.append(
                el('text', {
                    x: (x0 + x1) / 2,
                    y: midY + 7,
                    'text-anchor': 'middle',
                    fill: v('--tl-ink3'),
                    'font-size': 8.5,
                    class: 'tl-mono',
                    text: `${shortDate(this.days[from].date)} → ${shortDate(this.days[this.days.length - 1].date)}`,
                }),
            );

            return g;
        },

        hitAreas() {
            const g = el('g');
            this.hits = [];

            this.days.forEach((d, i) => {
                const rect = el('rect', {
                    class: 'tl-day',
                    x: this.dx(i),
                    y: Y.t0 - 2,
                    width: this.cw,
                    height: Y.H - Y.t0 - 2,
                    fill: 'transparent',
                    tabindex: 0,
                    role: 'button',
                    'aria-label': this.dayLabel(d),
                    rx: 3,
                });

                rect.addEventListener('focus', () => {
                    this.focus = i;
                    this.showDayTip(i, null);
                });
                rect.addEventListener('blur', () => this.hideTip());
                rect.addEventListener('keydown', (e) => this.onDayKey(e, i));

                g.append(rect);
                this.hits.push(rect);
            });

            return g;
        },

        onDayKey(e, i) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const j = Math.max(0, Math.min(this.days.length - 1, i + (e.key === 'ArrowRight' ? 1 : -1)));
                if (e.shiftKey) {
                    this.sel = this.sel ? { from: this.anchor ?? this.sel.from, to: j } : { from: i, to: j };
                    this.anchor ??= i;
                    this.commit();
                }
                this.hits[j].focus();
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (e.shiftKey && this.sel) {
                    this.sel = { from: this.anchor ?? this.sel.from, to: i };
                } else if (this.sel && this.sel.from === i && this.sel.to === i) {
                    this.sel = null;
                } else {
                    this.sel = { from: i, to: i };
                    this.anchor = i;
                }
                this.commit();
                this.showDayTip(i, null);
            } else if (e.key === 'Home') {
                e.preventDefault();
                this.hits[0].focus();
            } else if (e.key === 'End') {
                e.preventDefault();
                this.hits[this.days.length - 1].focus();
            }
        },

        bindBrush(svg) {
            let drag = null;
            let moved = false;

            const dayAt = (e) => {
                const pt = svg.createSVGPoint();
                pt.x = e.clientX;
                pt.y = e.clientY;
                const p = pt.matrixTransform(svg.getScreenCTM().inverse());
                let best = 0;
                let dist = Infinity;
                this.days.forEach((d, i) => {
                    const gap = Math.abs(this.dcx(i) - p.x);
                    if (gap < dist) {
                        dist = gap;
                        best = i;
                    }
                });

                return { i: best, x: p.x };
            };

            svg.addEventListener('pointerdown', (e) => {
                if (e.button !== 0) {
                    return;
                }
                const { i, x } = dayAt(e);
                if (x < TL.L - 4) {
                    return;
                }
                svg.setPointerCapture(e.pointerId);
                moved = false;

                if (e.shiftKey && this.sel) {
                    this.sel = { from: this.anchor ?? this.sel.from, to: i };
                    this.commit();

                    return;
                }
                drag = { start: i, previous: this.sel ? { ...this.sel } : null };
            });

            svg.addEventListener('pointermove', (e) => {
                const { i, x } = dayAt(e);
                if (x < TL.L - 4) {
                    this.hideTip();

                    return;
                }
                if (drag) {
                    if (i !== drag.start) {
                        moved = true;
                    }
                    this.sel = { from: drag.start, to: i };
                    this.anchor = drag.start;
                    this.paintSelection();
                }
                this.showDayTip(i, e);
            });

            svg.addEventListener('pointerup', (e) => {
                if (!drag) {
                    return;
                }
                const { i } = dayAt(e);
                const wasSingle = drag.previous && drag.previous.from === drag.previous.to && drag.previous.from === i;

                if (!moved) {
                    if (wasSingle && !e.shiftKey) {
                        this.sel = null;
                    } else {
                        this.sel = { from: i, to: i };
                        this.anchor = i;
                    }
                }
                drag = null;
                this.commit();
            });

            svg.addEventListener('dblclick', () => {
                this.sel = null;
                this.commit();
            });

            svg.addEventListener('pointerleave', () => this.hideTip());
        },

        toggleLayer(name) {
            this.layers[name] = !this.layers[name];
            this.applyLayers();
        },

        applyLayers() {
            this.svg.querySelectorAll('[data-layer]').forEach((g) => {
                g.style.opacity = this.layers[g.dataset.layer] ? '' : '.15';
            });
        },

        clear() {
            this.sel = null;
            this.anchor = null;
            this.commit();
            this.hideTip();
        },

        /** Só o gesto concluído vira evento: arrastar não deve conversar com o servidor. */
        commit() {
            this.paintSelection();

            const range = this.sel
                ? {
                      from: this.days[Math.min(this.sel.from, this.sel.to)].date,
                      to: this.days[Math.max(this.sel.from, this.sel.to)].date,
                  }
                : null;

            this.$wire?.dispatch('activity-timeline-selected', { range });
        },

        paintSelection() {
            this.overlay.innerHTML = '';

            if (!this.sel) {
                return;
            }

            const a = Math.min(this.sel.from, this.sel.to);
            const b = Math.max(this.sel.from, this.sel.to);
            const x0 = this.dx(0);
            const xa = this.dx(a) - 1;
            const xb = this.dx(b) + this.cw + 1;
            const x1 = this.dx(this.days.length - 1) + this.cw;
            const y0 = Y.t0 - 3;
            const y1 = Y.H - 2;

            // Dois blocos: o vão pula a tira da legenda do streamgraph.
            [
                [y0, Y.stream[1] + 1],
                [Y.msg[0] - 4, y1],
            ].forEach(([ya, yb]) => {
                if (xa > x0) {
                    this.overlay.append(
                        el('rect', {
                            x: x0,
                            y: ya,
                            width: xa - x0,
                            height: yb - ya,
                            fill: v('--tl-bg0'),
                            opacity: 0.62,
                        }),
                    );
                }
                if (xb < x1) {
                    this.overlay.append(
                        el('rect', {
                            x: xb,
                            y: ya,
                            width: x1 - xb,
                            height: yb - ya,
                            fill: v('--tl-bg0'),
                            opacity: 0.62,
                        }),
                    );
                }
            });

            this.overlay.append(
                el('rect', {
                    x: xa,
                    y: y0,
                    width: xb - xa,
                    height: y1 - y0,
                    fill: 'none',
                    stroke: v('--tl-acc2'),
                    'stroke-width': 1,
                    rx: 4,
                }),
            );
            this.overlay.append(el('rect', { x: xa, y: y0 - 1, width: xb - xa, height: 2, fill: v('--tl-acc2') }));
        },

        dayLabel(d) {
            if (!d.has) {
                return `${longDate(d.date)}: sem coleta`;
            }

            return `${longDate(d.date)}: ${fmt(d.gh.total)} contribuições no GitHub, ${fmt(d.ms.messages)} mensagens, ${fmt(d.vc.sessions)} sessões de voz`;
        },

        showDayTip(i, e) {
            const d = this.days[i];
            this.crosshair.setAttribute('transform', `translate(${this.dcx(i)},0)`);
            this.crosshair.setAttribute('opacity', 1);

            const inSelection =
                this.sel && i >= Math.min(this.sel.from, this.sel.to) && i <= Math.max(this.sel.from, this.sel.to);
            let html = `<div class="tl-tip-h">${longDate(d.date)}<small>dia ${i + 1}/${this.days.length}${this.sel ? ` · ${inSelection ? 'na seleção' : 'fora da seleção'}` : ''}</small></div>`;

            if (!d.has) {
                html += `<div class="tl-tip-n">Sem coleta — dado ingerido só até ${shortDate(this.meta.dataUntil)}.</div>`;
            } else {
                html += this.tipRow('GitHub · contribuições', fmt(d.gh.total), v(LV[levelOf(d.gh.total, this.th.gh)]));
                html += `<div style="padding-left:14px">${this.types.map((t) => this.tipRow(t.label, fmt(d.gh[t.key]), t.color, this.hoverType === t.key)).join('')}</div>`;
                html += this.tipRow('pessoas ativas', fmt(d.gh.people));
                html += '<div class="tl-tip-sep"></div>';
                html += this.tipRow(
                    'Discord · mensagens',
                    fmt(d.ms.messages),
                    v(LV[levelOf(d.ms.messages, this.th.ms)]),
                );
                html += this.tipRow('pessoas conversando', fmt(d.ms.people));
                html += this.tipRow('XP de chat', fmt(d.ms.xp));
                html += '<div class="tl-tip-sep"></div>';
                html += this.tipRow('Voz · sessões', fmt(d.vc.sessions), v(LV[levelOf(d.vc.sessions, this.th.vc)]));
                html += this.tipRow('pessoas em call', fmt(d.vc.people));
                html += this.tipRow('XP em call', fmt(d.vc.xp));
            }

            this.tip.html = html;
            this.tip.open = true;

            const anchor = e ?? this.hits[i].getBoundingClientRect();
            this.moveTip(e ? e.clientX : anchor.left + anchor.width / 2, e ? e.clientY : anchor.top + 40);
        },

        tipRow(key, value, color, highlight) {
            const swatch = color ? `<i style="background:${color}"></i>` : '';

            return `<div class="tl-tip-r${highlight ? ' is-on' : ''}"><span>${swatch}${esc(key)}</span><b>${esc(value)}</b></div>`;
        },

        moveTip(x, y) {
            const node = this.$refs.tip;
            const box = node.getBoundingClientRect();
            let left = x + 14;
            let top = y + 14;

            if (left + box.width > window.innerWidth - 8) {
                left = x - box.width - 14;
            }
            if (top + box.height > window.innerHeight - 8) {
                top = y - box.height - 14;
            }

            node.style.left = `${Math.max(4, left)}px`;
            node.style.top = `${Math.max(4, top)}px`;
        },

        hideTip() {
            this.tip.open = false;
            this.crosshair?.setAttribute('opacity', 0);
        },
    };
}

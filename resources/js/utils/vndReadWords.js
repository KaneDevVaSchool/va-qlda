/**
 * Đọc số nguyên VND (0 … 999 tỷ) thành chữ tiếng Việt.
 */
const CH = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

/**
 * @param {number} n — 0…999
 * @param {boolean} isShort — nhóm cao nhất, hoặc nhóm đơn vị khi các nhân tố trung gian đều 0 (vd. 2.000.002)
 */
function readHundreds(n, isShort) {
    const tr = Math.floor(n / 100);
    const ch = Math.floor((n % 100) / 10);
    const dv = n % 10;
    const parts = [];

    if (tr > 0) {
        parts.push(`${CH[tr]} trăm`);
    } else if (!isShort && n > 0) {
        parts.push('không trăm');
    }

    if (ch === 0) {
        if (dv > 0) {
            if (tr > 0) {
                parts.push('linh');
                parts.push(CH[dv]);
            } else if (isShort) {
                parts.push(CH[dv]);
            } else {
                parts.push('linh');
                parts.push(CH[dv]);
            }
        }
    } else if (ch === 1) {
        parts.push('mười');
        if (dv === 5) parts.push('lăm');
        else if (dv === 1) parts.push('mốt');
        else if (dv > 0) parts.push(CH[dv]);
    } else {
        parts.push(`${CH[ch]} mươi`);
        if (dv === 1) parts.push('mốt');
        else if (dv === 5) parts.push('lăm');
        else if (dv > 0) parts.push(CH[dv]);
    }

    return parts.join(' ');
}

/**
 * @param {number} amount — số nguyên ≥ 0
 * @returns {string} ví dụ "Một triệu hai trăm ba mươi bốn nghìn đồng"
 */
export function readVndAmountVietnamese(amount) {
    if (!Number.isFinite(amount) || amount < 0) return '';
    const n = Math.min(Math.floor(amount), 999_999_999_999);
    if (n === 0) return 'Không đồng';

    const groups = [];
    let t = n;
    while (t > 0) {
        groups.push(t % 1000);
        t = Math.floor(t / 1000);
    }

    const nz = [];
    for (let i = 0; i < groups.length; i++) {
        if (groups[i] !== 0) nz.push({ scaleIdx: i, g: groups[i] });
    }
    const order = [...nz].reverse();

    const onlyMiddleZeros =
        groups.length >= 3 && groups.slice(1, -1).every((x) => x === 0);

    const scales = ['', 'nghìn', 'triệu', 'tỷ'];
    const parts = [];

    for (let j = 0; j < order.length; j++) {
        const { scaleIdx, g } = order[j];
        const isHighest = j === 0;
        const isLowest = j === order.length - 1;
        const isShort =
            isHighest || (isLowest && onlyMiddleZeros && groups.length >= 3 && g < 100);
        const text = readHundreds(g, isShort);
        const scale = scales[scaleIdx];
        parts.push(scale ? `${text} ${scale}`.trim() : text);
    }

    const out = parts.join(' ').replace(/\s+/g, ' ').trim();
    const cap = out.charAt(0).toUpperCase() + out.slice(1);
    return `${cap} đồng`;
}

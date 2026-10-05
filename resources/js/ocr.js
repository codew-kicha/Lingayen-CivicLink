// Document pre-check (PRD §4.1). Loaded on demand by the application form only, so neither library
// weighs on other pages. Runs entirely in the applicant's browser; only the extracted text is sent,
// and the server makes the actual call (App\Services\DocumentPrecheck).
import { createWorker } from 'tesseract.js';
import * as pdfjs from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const MAX_CHARACTERS = 20000; // matches the ocr_text.* validation rule
const MIN_READABLE = 40;      // matches DocumentPrecheck::MIN_READABLE_CHARACTERS

// One Tesseract worker for every upload on the page; the English model downloads once and is cached.
let worker;
const recognizer = () => (worker ??= createWorker('eng'));

const recognize = async (image) => (await (await recognizer()).recognize(image)).data.text;

// Typed PDFs carry their own text layer, which is faster and more accurate than OCR; only scanned
// PDFs (no text layer) have page 1 rendered to an image and read.
async function readPdf(file) {
    const pdf = await pdfjs.getDocument({ data: await file.arrayBuffer() }).promise;
    const page = await pdf.getPage(1);
    const layer = (await page.getTextContent()).items.map((item) => item.str).join(' ');

    if (normalize(layer).length >= MIN_READABLE) {
        return layer;
    }

    const viewport = page.getViewport({ scale: 2 });
    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvas, canvasContext: canvas.getContext('2d'), viewport }).promise;

    return recognize(canvas);
}

export async function readDocument(file) {
    const text = file.type === 'application/pdf' ? await readPdf(file) : await recognize(file);

    return text.slice(0, MAX_CHARACTERS);
}

// Same rule as DocumentPrecheck::evaluate, for instant feedback; the server's result is what is stored.
export function normalize(text) {
    return text.toLowerCase().replace(/[^a-z0-9]/g, '');
}

export function evaluate(text, keywords, minMatches) {
    const haystack = normalize(text);
    const found = keywords.filter((keyword) => haystack.includes(normalize(keyword)));

    if (haystack.length < MIN_READABLE) return { state: 'unreadable', found };

    return { state: found.length >= minMatches ? 'matched' : 'mismatch', found };
}

import { Router } from 'express';
import multer from 'multer';
import { parse } from 'csv-parse/sync';
import * as xlsx from 'xlsx';
import { prisma } from '../db.js';
import { requireAuth, type AuthRequest } from '../middleware/auth.js';
import { extractAsinFromUrl, generateDisclosure, generatePinVariants, suggestBoard } from '../utils/content.js';

const upload = multer({ limits: { fileSize: 10 * 1024 * 1024 } });
export const importsRouter = Router();

async function processRows(userId: string, rows: any[], sourceType: string, filename?: string) {
  const batch = await prisma.importBatch.create({ data: { userId, sourceType, filename, rowCount: rows.length } });
  for (const row of rows) {
    const sourceUrl = row.source_url || row.url;
    const asin = row.asin || (sourceUrl ? extractAsinFromUrl(sourceUrl) : null);
    const product = await prisma.productItem.create({ data: { importBatchId: batch.id, sourceUrl, asin, affiliateUrl: row.affiliate_url, titleOverride: row.title_override, descriptionOverride: row.description_override, boardName: row.board_name, keywords: row.keywords, imageUrl: row.image_url, brand: row.brand, priceText: row.price_text, notes: row.notes, rawInput: JSON.stringify(row) } });
    const variants = generatePinVariants(row.title_override || `Product ${asin || ''}`.trim(), row.description_override || 'Check this out', row.keywords);
    for (const variant of variants) {
      await prisma.draftPin.create({ data: { userId, productItemId: product.id, tone: 'deal', variantType: variant.variantType, title: variant.title, description: variant.description, disclosure: generateDisclosure('short'), destinationLink: row.affiliate_url || sourceUrl || '', keywords: row.keywords, status: 'draft' } });
    }
    suggestBoard(row.board_name);
  }
  return batch;
}

importsRouter.post('/upload', requireAuth, upload.single('file'), async (req: AuthRequest, res) => {
  if (!req.file) return res.status(400).json({ error: 'file required' });
  const isCsv = req.file.originalname.endsWith('.csv');
  const rows = isCsv ? parse(req.file.buffer.toString(), { columns: true, skip_empty_lines: true }) : xlsx.utils.sheet_to_json(xlsx.read(req.file.buffer).Sheets[xlsx.read(req.file.buffer).SheetNames[0]]);
  const batch = await processRows(req.userId!, rows, isCsv ? 'csv' : 'xlsx', req.file.originalname);
  res.json(batch);
});

importsRouter.post('/paste', requireAuth, async (req: AuthRequest, res) => {
  const rows = String(req.body.text || '').split('\n').filter(Boolean).map((u) => ({ source_url: u.trim() }));
  const batch = await processRows(req.userId!, rows, 'paste');
  res.json(batch);
});
importsRouter.get('/:id', requireAuth, async (req, res) => res.json(await prisma.importBatch.findUnique({ where: { id: req.params.id }, include: { products: true } })));

import { Router } from 'express';
import { prisma } from '../db.js';
import { requireAuth } from '../middleware/auth.js';
import { generatePinVariants } from '../utils/content.js';

export const draftsRouter = Router();
draftsRouter.get('/', requireAuth, async (req, res) => {
  const { boardId, status, keyword } = req.query;
  res.json(await prisma.draftPin.findMany({ where: { boardId: boardId as string | undefined, status: status as any, keywords: keyword ? { contains: String(keyword) } : undefined }, include: { creativeAsset: true, board: true, productItem: true } }));
});
draftsRouter.get('/:id', requireAuth, async (req, res) => res.json(await prisma.draftPin.findUnique({ where: { id: req.params.id }, include: { publishLogs: true } })));
draftsRouter.patch('/:id', requireAuth, async (req, res) => res.json(await prisma.draftPin.update({ where: { id: req.params.id }, data: req.body })));
draftsRouter.post('/bulk-update', requireAuth, async (req, res) => {
  const { ids, data } = req.body;
  await prisma.draftPin.updateMany({ where: { id: { in: ids } }, data });
  res.json({ ok: true });
});
draftsRouter.post('/:id/regenerate', requireAuth, async (req, res) => {
  const d = await prisma.draftPin.findUnique({ where: { id: req.params.id } });
  if (!d) return res.status(404).json({ error: 'Not found' });
  res.json(generatePinVariants(d.title, d.description, d.keywords || ''));
});

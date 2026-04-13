import { Router } from 'express';
import { requireAuth, type AuthRequest } from '../middleware/auth.js';
import { prisma } from '../db.js';

export const analyticsRouter = Router();
analyticsRouter.get('/overview', requireAuth, async (req: AuthRequest, res) => {
  const userId = req.userId!;
  const [imports, drafts, published, failed] = await Promise.all([
    prisma.importBatch.count({ where: { userId } }),
    prisma.draftPin.count({ where: { userId } }),
    prisma.draftPin.count({ where: { userId, status: 'published' } }),
    prisma.draftPin.count({ where: { userId, status: 'failed' } })
  ]);
  res.json({ imports, drafts, published, failed });
});
analyticsRouter.get('/pins', requireAuth, async (req: AuthRequest, res) => res.json(await prisma.analyticsSnapshot.findMany({ where: { userId: req.userId! } })));

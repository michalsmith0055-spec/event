import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { prisma } from '../db.js';

export const complianceRouter = Router();
complianceRouter.get('/report', requireAuth, async (_req, res) => {
  const recent = await prisma.complianceEvent.findMany({ take: 100, orderBy: { createdAt: 'desc' } });
  res.json({ events: recent, warningCount: recent.filter((x) => x.severity !== 'low').length });
});

import { Router } from 'express';
import { prisma } from '../db.js';
import { requireAuth } from '../middleware/auth.js';
import { publishQueue } from '../queue/publishQueue.js';

export const publishRouter = Router();

publishRouter.post('/now', requireAuth, async (req, res) => {
  const { draftPinIds } = req.body as { draftPinIds: string[] };
  const jobs = await Promise.all(draftPinIds.map((id) => publishQueue.add('publish', { draftPinId: id })));
  await prisma.draftPin.updateMany({ where: { id: { in: draftPinIds } }, data: { status: 'publishing' } });
  res.json({ queued: jobs.length });
});

publishRouter.post('/schedule', requireAuth, async (req, res) => {
  const { draftPinIds, scheduleAt } = req.body;
  const target = new Date(scheduleAt).getTime();
  for (const id of draftPinIds) {
    await publishQueue.add('publish', { draftPinId: id }, { delay: Math.max(target - Date.now(), 1000) });
    await prisma.draftPin.update({ where: { id }, data: { status: 'scheduled', scheduleAt: new Date(scheduleAt) } });
  }
  res.json({ scheduled: draftPinIds.length });
});

publishRouter.post('/retry/:id', requireAuth, async (req, res) => {
  await publishQueue.add('publish', { draftPinId: req.params.id });
  res.json({ retried: req.params.id });
});

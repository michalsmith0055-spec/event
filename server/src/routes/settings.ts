import { Router } from 'express';
import { requireAuth, type AuthRequest } from '../middleware/auth.js';
import { prisma } from '../db.js';

export const settingsRouter = Router();
settingsRouter.get('/', requireAuth, async (req: AuthRequest, res) => res.json(await prisma.appSetting.findUnique({ where: { userId: req.userId! } })));
settingsRouter.patch('/', requireAuth, async (req: AuthRequest, res) => res.json(await prisma.appSetting.update({ where: { userId: req.userId! }, data: req.body })));

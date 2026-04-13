import { Router } from 'express';
import { requireAuth, type AuthRequest } from '../middleware/auth.js';
import { PinterestAuthService, PinterestBoardService } from '../services/pinterest.js';
import { prisma } from '../db.js';
import { encrypt } from '../utils/crypto.js';

const authService = new PinterestAuthService();
const boardService = new PinterestBoardService();
export const pinterestRouter = Router();

pinterestRouter.get('/oauth/start', requireAuth, async (_req, res) => {
  res.json({ url: authService.startOAuth('state-demo') });
});

pinterestRouter.get('/oauth/callback', requireAuth, async (req: AuthRequest, res) => {
  const token = await authService.exchangeCode();
  await prisma.pinterestConnection.create({ data: { userId: req.userId!, accountName: 'Demo Business', accessTokenEnc: encrypt(token.access_token), scope: token.scope } });
  res.json({ connected: true });
});

pinterestRouter.get('/boards', requireAuth, async (_req, res) => res.json(await boardService.listBoards()));
pinterestRouter.post('/boards', requireAuth, async (req, res) => res.json(await boardService.createBoard(req.body.name)));

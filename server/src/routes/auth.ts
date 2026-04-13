import { Router } from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { prisma } from '../db.js';
import { config } from '../config.js';

export const authRouter = Router();
authRouter.post('/register', async (req, res) => {
  const { email, password } = req.body;
  const passwordHash = await bcrypt.hash(password, 10);
  const user = await prisma.user.create({ data: { email, passwordHash } });
  await prisma.appSetting.create({ data: { userId: user.id } });
  res.json({ id: user.id, email: user.email });
});
authRouter.post('/login', async (req, res) => {
  const { email, password } = req.body;
  const user = await prisma.user.findUnique({ where: { email } });
  if (!user || !(await bcrypt.compare(password, user.passwordHash))) return res.status(401).json({ error: 'Invalid credentials' });
  const token = jwt.sign({ userId: user.id }, config.jwtSecret, { expiresIn: '7d' });
  res.cookie(config.cookieName, token, { httpOnly: true, sameSite: 'lax' }).json({ ok: true });
});
authRouter.post('/logout', (_req, res) => res.clearCookie(config.cookieName).json({ ok: true }));

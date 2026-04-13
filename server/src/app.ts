import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import cookieParser from 'cookie-parser';
import rateLimit from 'express-rate-limit';
import pinoHttp from 'pino-http';
import { logger } from './utils/logger.js';
import { authRouter } from './routes/auth.js';
import { pinterestRouter } from './routes/pinterest.js';
import { importsRouter } from './routes/imports.js';
import { draftsRouter } from './routes/drafts.js';
import { publishRouter } from './routes/publish.js';
import { analyticsRouter } from './routes/analytics.js';
import { complianceRouter } from './routes/compliance.js';
import { settingsRouter } from './routes/settings.js';

export const app = express();
app.use(helmet());
app.use(cors({ origin: true, credentials: true }));
app.use(express.json({ limit: '2mb' }));
app.use(cookieParser());
app.use(pinoHttp({ logger }));

const authLimiter = rateLimit({ windowMs: 15 * 60 * 1000, max: 50 });
const importLimiter = rateLimit({ windowMs: 15 * 60 * 1000, max: 30 });

app.get('/health', (_req, res) => res.json({ ok: true }));
app.use('/api/auth', authLimiter, authRouter);
app.use('/api/pinterest', pinterestRouter);
app.use('/api/imports', importLimiter, importsRouter);
app.use('/api/drafts', draftsRouter);
app.use('/api/publish', publishRouter);
app.use('/api/analytics', analyticsRouter);
app.use('/api/compliance', complianceRouter);
app.use('/api/settings', settingsRouter);

app.use((err: Error, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  logger.error({ err }, 'Unhandled error');
  res.status(500).json({ error: err.message });
});

import dotenv from 'dotenv';
dotenv.config();

export const config = {
  port: Number(process.env.PORT || 4000),
  jwtSecret: process.env.JWT_SECRET || 'dev-secret',
  cookieName: process.env.COOKIE_NAME || 'app_session',
  demoMode: process.env.DEMO_MODE === 'true',
  redisUrl: process.env.REDIS_URL || 'redis://localhost:6379',
  pinterestClientId: process.env.PINTEREST_CLIENT_ID || '',
  pinterestClientSecret: process.env.PINTEREST_CLIENT_SECRET || '',
  pinterestRedirectUri: process.env.PINTEREST_REDIRECT_URI || '',
  tokenEncryptionKey: process.env.TOKEN_ENCRYPTION_KEY || '12345678901234567890123456789012'
};

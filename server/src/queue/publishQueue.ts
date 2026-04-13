import { Queue, Worker } from 'bullmq';
import IORedis from 'ioredis';
import { config } from '../config.js';
import { prisma } from '../db.js';

const connection = new IORedis(config.redisUrl, { maxRetriesPerRequest: null });
export const publishQueue = new Queue('publish', { connection });

export const publishWorker = new Worker('publish', async (job) => {
  const draftPinId = job.data.draftPinId as string;
  await prisma.draftPin.update({ where: { id: draftPinId }, data: { status: 'publishing' } });
  await prisma.publishLog.create({ data: { draftPinId, level: 'info', message: 'Publishing started' } });
  await prisma.draftPin.update({ where: { id: draftPinId }, data: { status: 'published', publishedAt: new Date() } });
  await prisma.publishLog.create({ data: { draftPinId, level: 'info', message: 'Published (demo)' } });
}, { connection });

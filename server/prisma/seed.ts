import bcrypt from 'bcryptjs';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();
async function main() {
  const user = await prisma.user.upsert({
    where: { email: 'demo@example.com' },
    update: {},
    create: { email: 'demo@example.com', passwordHash: await bcrypt.hash('password123', 10) }
  });
  await prisma.appSetting.upsert({ where: { userId: user.id }, update: {}, create: { userId: user.id, timezone: 'America/New_York', defaultDisclosure: '#affiliate' } });
  const batch = await prisma.importBatch.create({ data: { userId: user.id, sourceType: 'demo', rowCount: 1 } });
  const product = await prisma.productItem.create({ data: { importBatchId: batch.id, asin: 'B00DEMO123', affiliateUrl: 'https://amazon.com/dp/B00DEMO123?tag=demo-20', titleOverride: 'Demo Coffee Grinder' } });
  await prisma.draftPin.create({ data: { userId: user.id, productItemId: product.id, tone: 'deal', variantType: 'conservative', title: 'Demo Coffee Grinder for Home Baristas', description: 'Compact, stylish, and giftable.', disclosure: '#affiliate', destinationLink: 'https://amazon.com/dp/B00DEMO123?tag=demo-20' } });
}
main().finally(() => prisma.$disconnect());

import { z } from 'zod';

export const importRowSchema = z.object({
  source_url: z.string().optional(),
  asin: z.string().optional(),
  affiliate_url: z.string().optional(),
  title_override: z.string().optional(),
  description_override: z.string().optional(),
  board_name: z.string().optional(),
  keywords: z.string().optional(),
  image_url: z.string().optional(),
  brand: z.string().optional(),
  price_text: z.string().optional(),
  notes: z.string().optional()
});

export function extractAsinFromUrl(url: string): string | null {
  const m = url.match(/(?:dp|gp\/product)\/([A-Z0-9]{10})/i);
  return m?.[1]?.toUpperCase() ?? null;
}
export function normalizeAmazonUrl(url: string): string { return url.split('?')[0]; }
export function validateAffiliateLink(url: string): boolean { return /amazon\./i.test(url) && /(tag=|ascsubtag=)/i.test(url); }
export function generateDisclosure(kind: string, custom?: string): string {
  if (kind === 'long') return 'As an Amazon Associate I may earn from qualifying purchases.';
  if (kind === 'custom' && custom) return custom;
  return '#affiliate';
}
export function generatePinVariants(title: string, desc: string, keywords = '') {
  return [
    { variantType: 'conservative', title: title.slice(0, 100), description: `${desc} ${keywords}`.trim() },
    { variantType: 'click-worthy', title: `Top Pick: ${title}`.slice(0, 100), description: `Don’t miss this find. ${desc}`.slice(0, 500) },
    { variantType: 'SEO-friendly', title: `${title} | Buying Guide`.slice(0, 100), description: `${desc} Keywords: ${keywords}`.slice(0, 500) }
  ];
}
export function suggestBoard(boardName?: string) { return boardName || 'General'; }
export function buildScheduleSlots(start: Date, count: number, gapMinutes: number) { return Array.from({ length: count }, (_, i) => new Date(start.getTime() + i * gapMinutes * 60000)); }
export function detectDuplicates(url: string, boardId: string, existing: {url: string; boardId: string}[]) { return existing.some((x) => x.url === url && x.boardId === boardId); }
export function validateCreativeInputs(mode: string, source?: string) { return mode === 'TEMPLATE_CREATIVE' || Boolean(source); }

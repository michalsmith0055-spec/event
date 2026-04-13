export type AmazonInput = { asin?: string; sourceUrl?: string; affiliateUrl?: string };
export type EnrichedProduct = { asin: string; title: string; description: string; affiliateUrl: string; imageUrl?: string };

export interface AmazonProvider {
  validateInput(input: AmazonInput): Promise<{ valid: boolean; issues: string[] }>;
  enrichProduct(input: AmazonInput): Promise<EnrichedProduct | null>;
  normalizeAffiliateUrl(url: string): Promise<string>;
  validateCompliance(input: AmazonInput): Promise<{ compliant: boolean; warnings: string[] }>;
}

export class MockAmazonProvider implements AmazonProvider {
  async validateInput(input: AmazonInput) { return { valid: Boolean(input.asin || input.sourceUrl), issues: [] }; }
  async enrichProduct(input: AmazonInput) {
    if (!input.asin) return null;
    return { asin: input.asin, title: `Demo Product ${input.asin}`, description: 'Demo-mode metadata from approved mock provider.', affiliateUrl: input.affiliateUrl || `https://amazon.com/dp/${input.asin}?tag=demo-20` };
  }
  async normalizeAffiliateUrl(url: string) { return url.trim(); }
  async validateCompliance() { return { compliant: true, warnings: ['Demo provider in use.'] }; }
}

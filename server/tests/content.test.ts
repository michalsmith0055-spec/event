import { describe, it, expect } from 'vitest';
import { extractAsinFromUrl, validateAffiliateLink, generateDisclosure } from '../src/utils/content.js';

describe('content utils', () => {
  it('extracts ASIN', () => expect(extractAsinFromUrl('https://amazon.com/dp/B00ABC1234')).toBe('B00ABC1234'));
  it('validates affiliate link', () => expect(validateAffiliateLink('https://amazon.com/dp/B00ABC1234?tag=abc-20')).toBe(true));
  it('generates disclosure', () => expect(generateDisclosure('long')).toContain('Amazon Associate'));
});

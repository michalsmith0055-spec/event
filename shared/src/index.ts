export enum AmazonSourceMode {
  IMPORT_ONLY = 'IMPORT_ONLY',
  ASIN_ENRICHMENT = 'ASIN_ENRICHMENT',
  MANUAL_METADATA = 'MANUAL_METADATA'
}

export enum DraftStatus {
  imported = 'imported',
  draft = 'draft',
  approved = 'approved',
  scheduled = 'scheduled',
  publishing = 'publishing',
  published = 'published',
  failed = 'failed',
  skipped_duplicate = 'skipped_duplicate'
}

export type CreativeMode = 'USER_IMAGE' | 'TEMPLATE_CREATIVE' | 'EXTERNAL_IMAGE_URL';

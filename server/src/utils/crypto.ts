import crypto from 'crypto';
import { config } from '../config.js';

const ivLength = 16;
export function encrypt(text: string): string {
  const iv = crypto.randomBytes(ivLength);
  const key = Buffer.from(config.tokenEncryptionKey.padEnd(32, '0').slice(0, 32));
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  const encrypted = Buffer.concat([cipher.update(text), cipher.final()]);
  return `${iv.toString('hex')}:${encrypted.toString('hex')}`;
}

export function decrypt(payload: string): string {
  const [ivHex, encryptedHex] = payload.split(':');
  const iv = Buffer.from(ivHex, 'hex');
  const key = Buffer.from(config.tokenEncryptionKey.padEnd(32, '0').slice(0, 32));
  const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
  const decrypted = Buffer.concat([decipher.update(Buffer.from(encryptedHex, 'hex')), decipher.final()]);
  return decrypted.toString();
}

import axios from 'axios';

export class PinterestAuthService {
  startOAuth(state: string) {
    return `https://www.pinterest.com/oauth/?response_type=code&client_id=${process.env.PINTEREST_CLIENT_ID}&redirect_uri=${encodeURIComponent(process.env.PINTEREST_REDIRECT_URI || '')}&scope=boards:read,pins:read,pins:write&state=${state}`;
  }
  async exchangeCode() { return { access_token: 'demo-token', scope: 'boards:read,pins:write' }; }
}

export class PinterestBoardService {
  async listBoards() { return [{ id: 'demo-board', name: 'Demo Board' }]; }
  async createBoard(name: string) { return { id: `board-${Date.now()}`, name }; }
}
export class PinterestMediaService { async uploadMedia(imageUrl: string) { return { mediaId: `media-${Date.now()}`, imageUrl }; } }
export class PinterestPinService {
  async createPin(payload: { boardId: string; title: string; description: string; mediaId: string; link: string }) {
    return { id: `pin-${Date.now()}`, ...payload };
  }
  async listRecentPins() { return [{ id: 'pin-1', title: 'Recent Demo Pin' }]; }
}
export class PinterestAnalyticsService { async getPinAnalytics() { return [{ pinId: 'pin-1', clicks: 10, saves: 4 }]; } }

export const pinterestHttp = axios.create({ baseURL: 'https://api.pinterest.com/v5' });

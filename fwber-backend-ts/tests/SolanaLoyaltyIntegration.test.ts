import { jest } from '@jest/globals';

const mockCreate = jest.fn();
const mockCount = jest.fn();
const mockPrisma = {
  autonomous_actions: { create: mockCreate },
  venue_checkins: { count: mockCount }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
}));

const { SolanaBridgeService } = await import('../src/services/SolanaBridgeService.js');

describe('Solana Loyalty Bridge Integration', () => {
  let bridgeService: any;

  beforeEach(() => {
    jest.clearAllMocks();
    bridgeService = new SolanaBridgeService();
  });

  it('should trigger NFT minting signal exactly at the threshold of 5 check-ins', async () => {
    const userId = 101;
    const merchantId = 505;

    // Simulate 5 separate check-in events
    for (let i = 1; i <= 5; i++) {
      mockCount.mockResolvedValueOnce(i);
      await bridgeService.recordLoyaltyEvent(userId, merchantId, 'check-in');
    }

    // Filter logs by type and status
    const allCalls = mockCreate.mock.calls.map((call: any) => call[0]);

    const mintLogs = allCalls.filter((call: any) =>
      call.data.task.includes('Solana NFT Mint') && call.data.status === 'Started'
    );

    expect(mintLogs.length).toBe(1);

    // In signalNftMint, metadata is the 3rd argument to logAction
    // logAction(task, status, metadata) calls prisma.create({ data: { task, status, metadata } })
    // So log.data.metadata is { userId, merchantId, metadata: { ... } }
    expect(mintLogs[0].data.metadata.metadata.checkin_count).toBe(5);

    const eventLogs = allCalls.filter((call: any) =>
      call.data.task.includes('Solana Loyalty Event') && call.data.status === 'Completed'
    );
    expect(eventLogs.length).toBe(5);
  });
});

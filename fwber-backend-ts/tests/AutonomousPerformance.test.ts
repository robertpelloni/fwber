import { jest } from '@jest/globals';

const mockCreate = jest.fn().mockImplementation(() => Promise.resolve({ id: 1 }));
const mockPrisma = {
  autonomous_actions: { create: mockCreate }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
}));

const { AutonomousService } = await import('../src/services/AutonomousService.js');

describe('AutonomousService Performance', () => {
  it('should handle high volume logging with low latency', async () => {
    const iterations = 1000;
    const start = performance.now();

    const promises = [];
    for (let i = 0; i < iterations; i++) {
      promises.push(AutonomousService.logAction(`Performance Task ${i}`, 'Completed', { iter: i }));
    }

    await Promise.all(promises);
    const end = performance.now();
    const totalTime = end - start;
    const avgTime = totalTime / iterations;

    console.log(`[Performance] Total time for ${iterations} logs: ${totalTime.toFixed(2)}ms`);
    console.log(`[Performance] Average time per log: ${avgTime.toFixed(4)}ms`);

    expect(mockCreate).toHaveBeenCalledTimes(iterations);
    expect(avgTime).toBeLessThan(5); // Average should be very fast for mocked DB
  });
});

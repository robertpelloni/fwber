import { jest } from '@jest/globals';

const mockEvaluateAction = jest.fn();
const mockLogAction = jest.fn();

jest.unstable_mockModule('../src/services/AutonomousDecisionEngine.js', () => ({
  AutonomousDecisionEngine: { evaluateAction: mockEvaluateAction },
  ProposedAction: {}
}));

jest.unstable_mockModule('../src/services/AutonomousService.js', () => ({
  AutonomousService: { logAction: mockLogAction }
}));

const { AutonomousTaskExecutor } = await import('../src/services/AutonomousTaskExecutor.js');

describe('AutonomousTaskExecutor', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should execute task when allowed', async () => {
    mockEvaluateAction.mockResolvedValue({ decision: 'Allow' });
    const task = jest.fn().mockResolvedValue('success');

    const result = await AutonomousTaskExecutor.execute(
      { type: 'Test Task', impact: 'low', module: 'Test' },
      task
    );

    expect(result).toBe('success');
    expect(mockLogAction).toHaveBeenCalledWith('Test Task', 'Started', expect.any(Object));
    expect(mockLogAction).toHaveBeenCalledWith('Test Task', 'Completed', expect.any(Object));
  });

  it('should throw error and log failure when denied', async () => {
    mockEvaluateAction.mockResolvedValue({ decision: 'Deny', reason: 'Too risky' });
    const task = jest.fn();

    await expect(AutonomousTaskExecutor.execute(
      { type: 'Risky Task', impact: 'high', module: 'Test' },
      task
    )).rejects.toThrow('Action Denied: Too risky');

    expect(mockLogAction).toHaveBeenCalledWith('Risky Task', 'Failed', expect.objectContaining({ reason: 'Too risky', status: 'Denied' }));
    expect(task).not.toHaveBeenCalled();
  });

  it('should log failure when task throws', async () => {
    mockEvaluateAction.mockResolvedValue({ decision: 'Allow' });
    const task = jest.fn().mockRejectedValue(new Error('Task failed'));

    await expect(AutonomousTaskExecutor.execute(
      { type: 'Failing Task', impact: 'low', module: 'Test' },
      task
    )).rejects.toThrow('Task failed');

    expect(mockLogAction).toHaveBeenCalledWith('Failing Task', 'Failed', expect.objectContaining({ error: 'Task failed' }));
  });
});

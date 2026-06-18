import { jest } from '@jest/globals';

const mockFindMany = jest.fn();
const mockUpdateMany = jest.fn();
const mockGenerateText = jest.fn();
const mockLogAction = jest.fn();

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: {
    proximity_artifacts: { findMany: mockFindMany },
    messages: { findMany: mockFindMany },
    user_profiles: { updateMany: mockUpdateMany }
  },
  serialize: (val: any) => val,
  sanitizeUser: (user: any) => user
}));

jest.unstable_mockModule('../src/lib/wingman-ai.js', () => ({
  generateText: mockGenerateText
}));

jest.unstable_mockModule('../src/services/AutonomousService.js', () => ({
  AutonomousService: { logAction: mockLogAction }
}));

const { SentimentAnalysisService } = await import('../src/services/SentimentAnalysisService.js');

describe('SentimentAnalysisService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should analyze user sentiment correctly', async () => {
    mockFindMany.mockResolvedValueOnce([{ content: 'Good morning Detroit!' }]);
    mockFindMany.mockResolvedValueOnce([{ content: 'I am so happy today.' }]);
    mockGenerateText.mockResolvedValue('Happy');

    const emotion = await SentimentAnalysisService.analyzeUserSentiment(1n);

    expect(emotion).toBe('Happy');
    expect(mockUpdateMany).toHaveBeenCalledWith(expect.objectContaining({
      data: expect.objectContaining({ current_emotion: 'Happy' })
    }));
  });
});

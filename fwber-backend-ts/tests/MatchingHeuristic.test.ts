import { jest } from '@jest/globals';
import { MatchingHeuristicService } from '../src/services/MatchingHeuristicService.js';

describe('MatchingHeuristicService', () => {
  let service: MatchingHeuristicService;

  beforeEach(() => {
    service = new MatchingHeuristicService();
  });

  it('should calculate satisfaction correctly', () => {
    const subjectAnswers = [
      {
        question_id: BigInt(1),
        chosen_option_id: BigInt(10),
        accepted_option_ids: [BigInt(20).toString()],
        importance: 2 // Somewhat important (10 pts)
      }
    ];

    const targetAnswers = [
      {
        question_id: BigInt(1),
        chosen_option_id: BigInt(20) // Matches accepted
      }
    ];

    // Access private method for testing logic
    const satisfaction = (service as any).calculateSatisfaction(subjectAnswers, targetAnswers);
    expect(satisfaction).toBe(1);
  });

  it('should calculate satisfaction as 0 if no options match', () => {
    const subjectAnswers = [
      {
        question_id: BigInt(1),
        chosen_option_id: BigInt(10),
        accepted_option_ids: [BigInt(20).toString()],
        importance: 2
      }
    ];

    const targetAnswers = [
      {
        question_id: BigInt(1),
        chosen_option_id: BigInt(30) // Doesn't match
      }
    ];

    const satisfaction = (service as any).calculateSatisfaction(subjectAnswers, targetAnswers);
    expect(satisfaction).toBe(0);
  });

  it('should calculate points based on importance', () => {
    expect((service as any).getImportancePoints(0)).toBe(0);
    expect((service as any).getImportancePoints(1)).toBe(1);
    expect((service as any).getImportancePoints(2)).toBe(10);
    expect((service as any).getImportancePoints(3)).toBe(50);
    expect((service as any).getImportancePoints(4)).toBe(250);
  });
});

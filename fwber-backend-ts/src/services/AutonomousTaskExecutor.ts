import { AutonomousService } from './AutonomousService.js';
import { AutonomousDecisionEngine, type ProposedAction } from './AutonomousDecisionEngine.js';

export class AutonomousTaskExecutor {
  /**
   * Execute a task with autonomous oversight.
   * Evaluates the action against the Decision Engine, logs the start, execution, and result.
   */
  static async execute<T>(
    action: ProposedAction,
    task: () => Promise<T>
  ): Promise<T> {
    const startTime = performance.now();
    const { decision, reason } = await AutonomousDecisionEngine.evaluateAction(action);

    if (decision === 'Deny') {
      await AutonomousService.logAction(action.type, 'Failed', {
        reason,
        status: 'Denied',
        impact: action.impact,
        module: action.module,
        decision_time_ms: performance.now() - startTime
      });
      throw new Error(`[AutonomousTaskExecutor] Action Denied: ${reason}`);
    }

    if (decision === 'Delegate') {
      await AutonomousService.logAction(action.type, 'Failed', {
        reason,
        status: 'Delegated',
        impact: action.impact,
        module: action.module,
        decision_time_ms: performance.now() - startTime
      });
      throw new Error(`[AutonomousTaskExecutor] Action Delegated: ${reason}`);
    }

    // decision === 'Allow'
    await AutonomousService.logAction(action.type, 'Started', {
      impact: action.impact,
      module: action.module,
      decision_time_ms: performance.now() - startTime
    });

    const executionStart = performance.now();
    try {
      const result = await task();
      const duration = performance.now() - executionStart;
      await AutonomousService.logAction(action.type, 'Completed', {
        duration_ms: duration,
        total_time_ms: performance.now() - startTime
      });
      return result;
    } catch (error: any) {
      const duration = performance.now() - executionStart;
      await AutonomousService.logAction(action.type, 'Failed', {
        error: error.message,
        impact: action.impact,
        module: action.module,
        duration_ms: duration,
        total_time_ms: performance.now() - startTime
      });
      throw error;
    }
  }
}

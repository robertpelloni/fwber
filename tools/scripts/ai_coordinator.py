#!/usr/bin/env python3
"""
AI Multi-Model Coordinator
Orchestrates multiple AI CLI tools to work together on tasks
"""

import subprocess
import json
import os
from pathlib import Path
from datetime import datetime
from typing import List, Dict, Any, Optional
import concurrent.futures
import time

class AICoordinator:
    """Coordinates multiple AI models working together"""
    
    def __init__(self, project_dir: str = r"C:\Users\hyper\fwber"):
        self.project_dir = project_dir
        self.results_dir = Path("AI_COORDINATION/orchestration_results")
        self.results_dir.mkdir(parents=True, exist_ok=True)
        
        # Available AI models
        self.models = {
            "codex": {
                "command": ["codex", "-c", "model_provider=anthropic", "exec"],
                "strengths": ["coding", "refactoring", "debugging"],
                "description": "GPT-based coding assistant"
            },
            "claude": {
                "command": ["claude", "-p"],
                "strengths": ["architecture", "analysis", "documentation"],
                "description": "Claude for system design"
            },
            "gemini": {
                "command": ["gemini"],
                "strengths": ["research", "explanation", "brainstorming"],
                "description": "Gemini for general tasks"
            }
        }
    
    def run_model(self, model_name: str, prompt: str, timeout: int = 120) -> Dict[str, Any]:
        """Run a single AI model with a prompt"""
        print(f"🤖 Running {model_name}...")
        
        if model_name not in self.models:
            return {
                "model": model_name,
                "success": False,
                "error": f"Unknown model: {model_name}",
                "output": None,
                "timestamp": datetime.now().isoformat()
            }
        
        model_config = self.models[model_name]
        command = model_config["command"] + [prompt]
        
        try:
            result = subprocess.run(
                command,
                capture_output=True,
                text=True,
                timeout=timeout,
                cwd=self.project_dir
            )
            
            success = result.returncode == 0
            output = result.stdout if success else result.stderr
            
            return {
                "model": model_name,
                "success": success,
                "output": output,
                "error": None if success else result.stderr,
                "timestamp": datetime.now().isoformat(),
                "strengths": model_config["strengths"]
            }
            
        except subprocess.TimeoutExpired:
            return {
                "model": model_name,
                "success": False,
                "error": f"Timeout after {timeout} seconds",
                "output": None,
                "timestamp": datetime.now().isoformat()
            }
        except Exception as e:
            return {
                "model": model_name,
                "success": False,
                "error": str(e),
                "output": None,
                "timestamp": datetime.now().isoformat()
            }
    
    def parallel_execution(self, models: List[str], prompt: str, timeout: int = 120) -> List[Dict[str, Any]]:
        """Execute the same prompt across multiple models in parallel"""
        print(f"\n🚀 Parallel Execution: {len(models)} models")
        print(f"📝 Prompt: {prompt[:100]}...")
        print()
        
        with concurrent.futures.ThreadPoolExecutor(max_workers=len(models)) as executor:
            futures = {
                executor.submit(self.run_model, model, prompt, timeout): model 
                for model in models
            }
            
            results = []
            for future in concurrent.futures.as_completed(futures):
                model = futures[future]
                try:
                    result = future.result()
                    results.append(result)
                    
                    status = "✓" if result["success"] else "✗"
                    print(f"{status} {model} completed")
                    
                except Exception as e:
                    print(f"✗ {model} failed: {e}")
                    results.append({
                        "model": model,
                        "success": False,
                        "error": str(e),
                        "output": None,
                        "timestamp": datetime.now().isoformat()
                    })
        
        return results
    
    def sequential_execution(self, workflow: List[Dict[str, str]]) -> List[Dict[str, Any]]:
        """Execute a workflow where each step uses output from previous step"""
        print(f"\n🔄 Sequential Execution: {len(workflow)} steps")
        print()
        
        results = []
        context = ""
        
        for i, step in enumerate(workflow, 1):
            model = step["model"]
            prompt_template = step["prompt"]
            
            # Insert context from previous step
            prompt = prompt_template.format(context=context)
            
            print(f"Step {i}/{len(workflow)}: {model}")
            print(f"  Prompt: {prompt[:80]}...")
            
            result = self.run_model(model, prompt)
            results.append(result)
            
            if result["success"]:
                context = result["output"]
                print(f"  ✓ Success ({len(context)} chars output)")
            else:
                print(f"  ✗ Failed: {result['error']}")
                break
            
            print()
        
        return results
    
    def consensus_execution(self, models: List[str], prompt: str, timeout: int = 120) -> Dict[str, Any]:
        """Get consensus from multiple models"""
        print(f"\n🤝 Consensus Execution: {len(models)} models")
        print()
        
        results = self.parallel_execution(models, prompt, timeout)
        successful_results = [r for r in results if r["success"]]
        
        consensus = {
            "prompt": prompt,
            "models": models,
            "total_models": len(models),
            "successful": len(successful_results),
            "failed": len(models) - len(successful_results),
            "results": results,
            "consensus": self._analyze_consensus(successful_results),
            "timestamp": datetime.now().isoformat()
        }
        
        return consensus
    
    def _analyze_consensus(self, results: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Analyze consensus from multiple model outputs"""
        if not results:
            return {"agreement": "none", "summary": "All models failed"}
        
        # Simple analysis - in a real system, this would use NLP to find common themes
        outputs = [r["output"] for r in results]
        
        # Check for common keywords
        all_words = set()
        for output in outputs:
            words = output.lower().split() if output else []
            all_words.update(words)
        
        common_words = []
        for word in all_words:
            count = sum(1 for output in outputs if output and word in output.lower())
            if count >= len(results) * 0.5:  # Present in 50%+ of outputs
                common_words.append(word)
        
        return {
            "agreement": "high" if len(common_words) > 10 else "moderate" if len(common_words) > 5 else "low",
            "common_themes": common_words[:20],  # Top 20 common words
            "model_count": len(results),
            "summary": f"Found {len(common_words)} common themes across {len(results)} models"
        }
    
    def save_results(self, results: Any, filename: str):
        """Save results to file"""
        filepath = self.results_dir / filename
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        print(f"\n💾 Results saved to: {filepath}")
        return filepath
    
    def debate_mode(self, models: List[str], topic: str, rounds: int = 2) -> List[Dict[str, Any]]:
        """Models debate a topic, responding to each other"""
        print(f"\n💬 Debate Mode: {len(models)} models, {rounds} rounds")
        print(f"📋 Topic: {topic}")
        print()
        
        debate_history = []
        context = f"Topic: {topic}\n\nPlease provide your initial position on this topic."
        
        for round_num in range(1, rounds + 1):
            print(f"\n--- Round {round_num} ---")
            
            for model in models:
                # Add previous arguments to context
                if debate_history:
                    other_args = "\n\n".join([
                        f"{r['model']}: {r['output'][:200]}..." 
                        for r in debate_history[-len(models):]
                        if r['model'] != model
                    ])
                    prompt = f"{context}\n\nPrevious arguments:\n{other_args}\n\nYour response:"
                else:
                    prompt = context
                
                result = self.run_model(model, prompt)
                debate_history.append(result)
                
                if result["success"]:
                    print(f"  ✓ {model} responded")
                else:
                    print(f"  ✗ {model} failed")
        
        return debate_history

def main():
    """Example usage"""
    coordinator = AICoordinator()
    
    print("=== AI Multi-Model Coordinator ===")
    print()
    
    # Example 1: Parallel execution
    print("Example 1: Parallel Code Review")
    print("-" * 50)
    
    models = ["codex", "claude"]
    prompt = "Review the main PHP files in the FWBer project. List any security concerns."
    
    results = coordinator.parallel_execution(models, prompt, timeout=60)
    coordinator.save_results(results, f"parallel_review_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
    
    # Example 2: Sequential workflow
    print("\n\nExample 2: Sequential Architecture Design")
    print("-" * 50)
    
    workflow = [
        {
            "model": "claude",
            "prompt": "Analyze the FWBer project structure and identify the main components."
        },
        {
            "model": "codex",
            "prompt": "Based on this analysis, suggest 3 specific code improvements:\n\n{context}"
        }
    ]
    
    sequential_results = coordinator.sequential_execution(workflow)
    coordinator.save_results(sequential_results, f"sequential_design_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
    
    # Example 3: Consensus
    print("\n\nExample 3: Consensus on Best Practices")
    print("-" * 50)
    
    consensus_prompt = "What are the top 3 best practices for PHP web application security?"
    consensus = coordinator.consensus_execution(["codex", "claude"], consensus_prompt)
    coordinator.save_results(consensus, f"consensus_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
    
    print("\n\n=== Coordination Complete ===")
    print(f"Results saved to: {coordinator.results_dir}")

if __name__ == "__main__":
    main()

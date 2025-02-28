# Prompts for code generation

English might be the best programming language to learn in the 21st century! The key to getting great code from your AI assistant is all in how you communicate your requests. I’ve learned (sometimes the hard way) that vague prompts lead to vague code. Here’s my go-to template for requesting code:

```
I need to implement [specific functionality] in [programming language].
Key requirements:
1. [Requirement 1]
2. [Requirement 2]
3. [Requirement 3]
Please consider:
- Error handling
- Edge cases
- Performance optimization
- Best practices for [language/framework]
Please do not unnecessarily remove any comments or code.
Generate the code with clear comments explaining the logic.
```

## Reviewing and Understanding AI-Generated Code
Now, here’s a crucial point: never, ever blindly copy-paste AI-generated code into your project. I made this mistake early on and spent hours debugging issues that I could have caught with a careful review.

```
Can you explain the following part of the code in detail:
[paste code section]
Specifically:
1. What is the purpose of this section?
2. How does it work step-by-step?
3. Are there any potential issues or limitations with this approach?
```

# Code Reviews and Improvements
```
Please review the following code:
[paste your code]
Consider:
1. Code quality and adherence to best practices
2. Potential bugs or edge cases
3. Performance optimizations
4. Readability and maintainability
5. Any security concerns
Suggest improvements and explain your reasoning for each suggestion.
```

# Prompt Ideas for Various Coding Tasks
## For implementing a specific algorithm
```
Implement a [name of algorithm] in [programming language]. 
Please include: 
1. The main function with clear parameter and return types 
2. Helper functions if necessary 
3. Time and space complexity analysis 
4. Example usage
```

## For creating a class or module:
```
Create a [class/module] for [specific functionality] in [programming language].
Include:
1. Constructor/initialization
2. Main methods with clear docstrings
3. Any necessary private helper methods
4. Proper encapsulation and adherence to OOP principles
```

## For optimizing existing code:
```
Here's a piece of code that needs optimization:
[paste code]
Please suggest optimizations to improve its performance. 
For each suggestion, explain the expected improvement and any trade-offs.
```

## For writing unit tests:
```
Generate unit tests for the following function:
[paste function]
Include tests for:
1. Normal expected inputs
2. Edge cases
3. Invalid inputs
Use [preferred testing framework] syntax.
```


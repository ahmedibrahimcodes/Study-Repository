# AI Transformers

## What is a Transformer?

A Transformer is a neural network architecture introduced in 2017 in the paper `Attention Is All You Need`.
It was designed primarily for processing sequential data such as natural language
and has become the foundation of almost every modern Large Language Model (LLM),
including GPT, Llama, Qwen, Gemma, Mistral, and many others.

Unlike previous architectures, a Transformer can process an entire sequence of tokens simultaneously instead of reading one token at a time.

## Embeddings

Embeddings existed long ago before Transformers and it is space of multiple dimensions can be 32 dimension. each point will be represented as a vector of 32 positions in the space

such as this point [0.43, -0.27, 1.11, ..., -0.08]

all words similar in the meaning such as Laravel,SymfonyCodeIgniter will be 3 points close together in the space

## What existed before Transformers?

Before 2017, most language models were based on Recurrent Neural Networks (RNNs)

Their main characteristic was that they processed text sequentially.

Example: I → love → programming → in → PHP

Each word depended on the computation of the previous word.

This made them similar to reading a sentence one word at a time.

### Problems with RNNs

- Sequential Processing

  The biggest limitation is that an RNN cannot process multiple words simultaneously.

  "I love programming in PHP." To process the word "programming", it must first finish processing "love", which itself depends on processing "I".

  This creates a chain of dependencies: Word 1 -> word 2 -> word 3 -> word 4

  Modern GPUs are designed to perform thousands of operations in parallel. Since an RNN must wait for each previous word before continuing, most of the GPU's parallel processing capability cannot be utilized.

  As sentences become longer, training becomes increasingly slow.

- Long-Term Memory Problems

  The hidden state is expected to carry all previously learned information.

  Imagine reading this sentence:

  "The report that John spent three months preparing after interviewing hundreds of customers was finally approved because it..."

  When the model reaches the word "it", it needs to remember what "it" refers to.

  Unfortunately, that information has been passed through many hidden states.

  Every step slightly changes the hidden state. Important information can gradually become weaker or even disappear before reaching the end of the sentence.

  This makes it difficult for RNNs to understand relationships between words that are far apart.

- Slow training

  Since every word depended on the previous one, GPUs could not fully utilize their thousands of processing cores.

  Training therefore became much slower.

- Limited Context

  Although an RNN can theoretically remember information from the beginning of a sentence, in practice it becomes increasingly difficult as the sequence grows longer.

  For example:

  "Ahmed, who started working at the company in 2015 after graduating from university and later moved into the infrastructure team before becoming a software architect, said he..."

  By the time the model reaches "he", it must remember that "he" refers to Ahmed, despite dozens of intermediate words.

  The longer the sentence becomes, the harder this task is for an RNN.

#### Why Transformers Solved These Problems

Transformers replaced the idea of passing information through a single hidden state.

Instead, they introduced self-attention, where every word can directly access every other word in the sentence.

Instead of information flowing like this: Word 1 → Word 2 → Word 3 → Word 4 → Word 5

it becomes:

```
          Word 1
        ↗   ↑   ↖
Word 2 ←→ Word 3 ←→ Word 4
        ↘   ↓   ↙
          Word 5
```

Each word can immediately attend to any other relevant word, regardless of how far apart they are.

and this utilized gpu then it can do parallel tasks quickly

which made tranining more quicker and also correctness is better

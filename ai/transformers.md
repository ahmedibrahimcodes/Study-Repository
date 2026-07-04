## Transformers

```
────────────────────────────────────
    Input Block N
────────────────────────────────────
Text
│
▼
Tokenizer
│
▼
Embedding Lookup
│
▼
Positional Encoding
│
▼
────────────────────────────────────
    Transformer Block Repeated N times (32 layers means repeated 32 times)
────────────────────────────────────
Self-Attention
Residual
LayerNorm
Feed-Forward
Residual
LayerNorm
────────────────────────────────────
│
▼
Linear Output Layer
│
▼
Softmax
│
▼
Next Token
```

### Text:

What is Docker?

### Tokenization

convert each word into token id. these are just integers and doesnt represent any meaning

What → 1251
is → 84
Docker → 19582
? → 31

### Embedding Layer

Now the embedding layer takes each ID and looks it up in a huge table.

| Token ID | Embedding        |
| -------: | ---------------- |
|     1251 | [0.3, -0.2, 0.5] |
|       84 | [0.8, 0.1, -0.6] |
|    19582 | [-0.5, 0.7, 0.4] |
|       31 | [0.1, -0.3, 0.2] |

note: part of the model's learned parameters is a large matrix called the `embedding matrix`

If: Vocabulary size = 150,000 tokens and Embedding dimension = 4096 Then the matrix has roughly: 150,000 x 4096 numbers = 614 million parameters. and Every row corresponds to one token.

Now imagine the whole model has 7b parameters: Then:

Embeddings
≈ 614 million

Everything else
≈ 6.4 billion

So the embedding layer is less than 10% of the entire model.

note: An embedding is the vector representation of a token.

Q: what if word is not on the embedding matrix of the model?

It is impossible for a valid token to not be in the embedding matrix.

- The tokenizer and embedding matrix are designed together so Every token ID produced by the tokenizer has a corresponding row.
  So if the tokenizer outputs: Docker → 19582 then row 19582 is guaranteed to exist.

- what if I type a completely new word? What is `ChatGPTXYZSuperUltra`?
  tokenizer will subword it and tokenize it as if it was `Chat GPT XYZ Super Ultra` or `Chat GP T XY Z Super Ultra` or in worst case
  `C h a t G P T X Y Z ...`

### Positional Encoding

Right now the model has the vector of each word but position is not considered yet so we need to add position into the embedding:

"What" .. Embedding [0.3, -0.2, 0.5] + Position 1 [0.01,0,0] = [0.31,-0.2,0.5]
"is" .. [0.8, 0.1, -0.6] + Position 2 [0,0.01,0] = [0.8,0.11,-0.6]
"docker" .. [-0.5, 0.7, 0.4] + Position 3 [0,0,0.01] = [-0.5,0.7,0.41]
"?" .. [0.1, -0.3, 0.2] + Position 4 [0.01,0.01,0] = [0.11,-0.29,0.2]

Every word now knows where it appears.

Q: but this position adding into the embedding, it may change the embedding to come closer another word?

Not really because:

- The position vector is tiny compared to the embedding. then the word don't change its meaning

- we already fetched the word from the embedding matrix and now it will go through the Transformer and Transformer is not trying to identify the original token from this vector anymore.
  The vector is no longer "the dictionary definition of PHP."
  It is now: "PHP, appearing in position 3 of this sentence." That's a different representation with a different purpose.

### Self Attention

"What is docker ?"
[0.31,-0.2,0.5]
[0.8,0.11,-0.6]
[-0.5,0.7,0.41]
[0.11,-0.29,0.2]

now it will inspect each embedding for example "what" "Which words help me understand "What"?"

| Looking FROM "What" | Score |
| ------------------- | ----: |
| What                |  0.20 |
| is                  |  0.60 |
| Docker              |  0.15 |
| ?                   |  0.05 |

Notice that "What" attends to itself. This is important because sometimes the word itself contains most of the useful information.

New_What = 0.20 x EI + 0.6 x EL + 0.15 x EP + .05 x ER

and same thing is done for each token in parallel

Q: since each word is inspected in parallel and it will update the embedding, now the other token will rely on the original embedding or the new embedding?

They all rely on the original embeddings from the beginning of the layer. They do NOT use the newly computed embeddings from other tokens in the same layer.

### Residual Connection

The paper of transformers discovered something interesting. Sometimes the attention layer makes the representation worse.
So instead of trusting it completely, they combine them.
Output = Original embedding + AttentionOutput embedding

### Layer Normalization

Imagine one vector contains numbers like: [500, -120, 900] while another contains: [0.01, -0.03, 0.04]

Neural networks learn better when values stay within a reasonable range. LayerNorm rescales the vector.

It doesn't change the meaning. It simply makes training more stable.

### Feed-Forward Network (FFN)

### Another Residual

Again they don't completely replace the vector. Again preserving useful information. They do: Output = Input + FFN(Input)

### Another LayerNorm

Normalize again.

### Next Transformer Block

it takes output from block 1 and give it as input to block 2 to give better representation

- Layer 1 : "This is probably software."
- Layer 6 : "It is a container platform."
- Layer 15: "The user is asking for a definition."
- Layer 28: "The answer should explain containers, images and isolation."

Each layer refines the representation.

### Finally

After all transformer blocks, we now have one vector for every token.

Let's say the final vector for the last token `?` is: [2.1, -0.7, ..., 0.3]

The model feeds this vector into the output layer. It computes probabilities for every token in the model vocabulary.

| Token     | Probability |
| --------- | ----------: |
| Docker    |     0.00001 |
| A         |        0.42 |
| It        |        0.25 |
| The       |        0.10 |
| Container |        0.05 |

it picks A then answer beings with A

### Repeat

Now the input becomes:

```
What is Docker?

A
```

The entire pipeline runs again from the beginning to predict the next token.

Maybe it predicts: container Then: platform Then: that .. One token at a time until the answer is complete.

### The intuition

I think of a Transformer block like this:

- Self-Attention: "Talk to everyone else."
- Residual: "Don't forget who you originally were."
- LayerNorm: "Keep your numbers well-behaved."
- Feed-Forward: "Think about everything you just learned."
- Residual: "Keep the useful old information too."
- LayerNorm: "Normalize again."

Then the next block repeats the exact same process.

One thing I think you'll find fascinating is that every Transformer block has exactly the same architecture. A model like a 32-layer Transformer is essentially the same block copied 32 times, each with different learned weights. That's why researchers often describe a Transformer as "one brilliant block repeated over and over."

Q: for 7B parameters where are these parameters?

7B model might look roughly like this:

| Component                          | Parameters |
| ---------------------------------- | ---------: |
| Embedding Matrix                   |      ~600M |
| Self-Attention (all layers)        |        ~2B |
| Feed-Forward Networks (all layers) |        ~4B |
| LayerNorm + Output Layer           |      ~400M |

Don't focus on the exact numbers—they vary by architecture—but notice the pattern: The Feed-Forward Networks usually contain the largest share of the parameters.

Self-attention decides which information to gather from the other tokens.

The Feed-Forward Network performs most of the heavy transformation of that information.

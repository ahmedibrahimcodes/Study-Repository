## AI

##[Before Transformers](./before_transformers.md)

##[Transformers](./transformers.md)

### Q: the more output data the more time taken by the ai to generate it right?

Yes, that's generally correct.

The important detail is how LLMs generate text. They don't generate an entire paragraph at once.
They generate one token at a time. For example, suppose you ask: Explain Docker.

The model might generate something like this:

`Docker`

Then it stops.

Now it predicts the next token:

`Docker is`

Then:

`Docker is a`

Then:

`Docker is a platform`

Then:

`Docker is a platform for`

...and so on until it decides to stop.

### Q: What affects the time per token?

Several things:

- Model size
  A 14B model is slower than a 7B model because it has roughly twice as many parameters to compute.
- Hardware
  - GPU vs CPU
  - Amount of VRAM
  - Memory bandwidth
- Context length
  If you've already had a long conversation, the model has more context to process before generating each new token. Modern Transformers optimize this with techniques like KV caching, but very long contexts can still reduce generation speed.

Is the input also expensive?

Yes, but in a different way.

When you send: 10,000 input tokens

the model first processes the entire input to build its internal representation. After that, it begins generating output one token at a time.

So the total time is approximately:

Time = Time to process the input + Time to generate each output token

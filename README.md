# Banking API

An in-memory banking API with deposit, withdraw and transfer operations.

Live at: https://banking-api-t1y4.onrender.com

> **Note:** on Render's free plan the container sleeps when idle, so the first request may
> be slow or time out. Retry once if the test suite fails on the first call.

## Running

With Docker:

```bash
docker build -t banking-api .
docker run -p 8080:8080 banking-api
```

The Docker path needs nothing else installed.

Without Docker (requires PHP 8.4 with the `dom`, `xml` and `mbstring` extensions, plus
Composer):

```bash
composer install
php bin/server.php
```

The server listens on the port given by the `PORT` environment variable, defaulting to `8080`.

## Tests

```bash
composer test
```

40 tests covering the domain rules and the HTTP layer.

## Endpoints

| Request | Success | Failure |
|---|---|---|
| `POST /reset` | `200` `OK` | — |
| `GET /balance?account_id=100` | `200` `<balance>` | `404` `0` |
| `POST /event` | `201` + account state | `404` `0`, `422` or `400` |

Event payloads:

```json
{"type": "deposit",  "destination": "100", "amount": 10}
{"type": "withdraw", "origin": "100", "amount": 5}
{"type": "transfer", "origin": "100", "destination": "300", "amount": 15}
```

## Design decisions

**Runtime: a long-running process, not PHP-FPM.** The spec requires in-memory state, and
under FPM every variable is destroyed at the end of each request. The fix is not a different
store, it is a process that stays alive. ReactPHP does that with one Composer package;
FrankenPHP, Swoole or Octane would work equally well.

Because the process is single-threaded, atomicity between concurrent requests comes from the
execution model rather than from locking.

**No framework.** The whole application is about 250 lines and needs no routing table, ORM
or container. A framework would add more code than it removes. There is one production
dependency.

**Structure.** `src/Domain` holds the business rules and `src/Http` the transport. Nothing
in `Domain` imports anything from `Http`: the domain does not know that HTTP exists. The
translation from domain exception to status code happens in a single place, `Router::handle`.

**Error responses.** The spec defines `404` with a bare `0` for accounts that do not exist,
and that is kept verbatim. Three conditions the spec leaves undefined:

- **Insufficient funds → `422`.** The account exists and the request is well formed; the
  business rule rejects it. Collapsing it into `404` would prevent a client from telling
  "wrong account number" from "not enough money", which call for different actions.
- **Non-positive amount → `422`.** A negative deposit is a withdrawal in disguise that
  bypasses the balance check. It is understood and refused, so it shares both the status
  and the `catch` clause with insufficient funds.
- **Unknown event type → `400`.** Here the request itself is wrong, not the state. Without
  it the API would answer `500` to a typo.

The rule that separates the two: `400` means the request was not understood, `422` means it
was understood and refused.

Withdrawing an entire balance is allowed: the rule is that a balance may not go negative,
not that it may not reach zero.

**`reset` lives in the domain.** Clearing a collection is a normal capability of a
collection, and keeping it there means the HTTP layer never owns the lifecycle of the
business state.

## Known limitations

- **Single instance.** State lives in one process, so the API does not scale horizontally.
  To scale, I would shard by `account_id` or move the state to a shared transactional store
  — and then pay for the atomicity that the current execution model provides for free.
- **No durability.** A restart loses all balances. The spec states durability is not a
  requirement; a real system would use an append-only ledger where the balance is a
  projection of the entries, rather than a number that gets overwritten.
- **No idempotency.** Events carry no identifier, so a retried request is applied twice.
  A production payments API would require an idempotency key per operation.
- **Type errors return `500`.** Malformed JSON, an empty body and a missing `type` are
  answered with `400`, but a field of the wrong type — `amount` as a string, an account id
  as a number — raises a `TypeError` under `strict_types` that nothing translates. The spec
  describes only well-formed events, so I did not add a validation layer for a case it does
  not define.
- **`POST /reset` is public and unauthenticated**, and it wipes every balance. It exists
  because the test suite must be repeatable — a testing affordance that leaked into the
  public contract.

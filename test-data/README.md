Development data regeneration
======

Get a session cookie by logging into the
[sirius integration environment](https://frontend-integration.dev.sirius.opg.digital) using administrator credentials.

Fetch the session id from your browser inspect tools. It will look something like "4sqpt2g2d147ts8m7gioqo9i0c"

```shell script
python3 generate-data.py 4sqpt2g2d147ts8m7gioqo9i0c
```

This will *append* the output to the **api-gateway.json** file. To do this properly you probably want to empty that file
first.

Optionally tidy the syntax of that file using some IDE tooling or `jq`.

**Assuming your environment is up and running (`docker-compose up`)**

```shell script
docker exec -it api-app php ./console.php actorcode:create `cat api-gateway.json| jq ".[] | .uId" | sed 's/["-]//g' | xargs | sed 's/ /,/g'`
```

This will write a set of actor codes for all lpa's loaded using the generate-data.py script into the dynamodb. Realistically
you probably want to also empty the ActorCodes table before you run this as the data in there will be inaccurate at this
point.

Now you'll need to move to the seeding folder to patch up the rest of the data

```shell script
cd ../service-api/seeding
```

```shell script
aws-vault exec identity -- python3 get_actor_codes.py
```

This will pull the new (old-style) codes from the Dynamo tables and create a json file containing correctly resolved
lpa data which you can now push to the Codes api mock service.

```shell script
mv /tmp/lpa_codes_local_YYYY-MM-DD.json seeding_lpa_codes.json
```

To update the local copy from the new temporary file

```shell script
aws-vault exec identity -- python3 put_actor_codes.py
```

Finally add the codes to the codes api tables for usage.

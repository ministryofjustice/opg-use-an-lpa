to_entries
| map_values(
    {LPAId :.key
    | sub( "(?<a>7[0-9]{3})(?<b>[0-9]{4})(?<c>[0-9]{4})";"\(.a)-\(.b)-\(.c)"
    )
    }
  + {
    	Codes: .value
    	| map_values(
      	{
          id,
          uId : .uId
			| sub( "(?<a>7[0-9]{3})(?<b>[0-9]{4})(?<c>[0-9]{4})";"\(.a)-\(.b)-\(.c)"),
      	  firstname,
          middlenames,
          surname,
          code : .code
           | sub( "(?<a>[A-Z0-9]{4})(?<b>[A-Z0-9]{4})(?<c>[A-Z0-9]{4})";"\(.a) \(.b) \(.c)"),
      	  expiry
    	})
	})

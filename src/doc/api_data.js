define({ "api": [  {    "type": "get",    "url": "/trial/:tid",    "title": "Returns the complete details for a given Trial ID",    "name": "GetTrial",    "version": "0.0.1",    "group": "Admin",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "get",    "url": "/user/details",    "title": "User Details",    "name": "GetUserDetails",    "version": "0.0.1",    "group": "Admin",    "permission": [      {        "name": "admin"      }    ],    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "Authorization",            "description": "<p>Admin's <code>access_token</code>.</p>"          }        ]      },      "examples": [        {          "title": "Header-Example:",          "content": "{\n  \"Authorization\": \"Bearer abc...xyz\"\n}",          "type": "String"        }      ]    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "uid",            "description": "<p>User's unique ID.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "email",            "description": "<p>User's email address.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>User's name.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "role",            "description": "<p>User's role.</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"uid\": \"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx\",\n  \"email\": \"user@mail.ca\",\n  \"name\": \"Reece Ercher\",\n  \"role\": \"admin\"\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "AccessDenied",            "description": "<p>The <code>access_token</code> provided has expired.</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 401 Unauthorized\n{\n  \"error\": \"access_denied\",\n  \"message\": \"The resource owner or authorization server denied the request.\",\n  \"hint\": \"Access token is invalid\"\n}",          "type": "json"        }      ]    },    "description": "<p>Returns user details based on the provided OAuth token.</p>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "get",    "url": "/user/trials",    "title": "List Trials",    "name": "GetUserTrials",    "version": "0.0.1",    "group": "Admin",    "permission": [      {        "name": "admin"      }    ],    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "Authorization",            "description": "<p>Admin's <code>access_token</code>.</p>"          }        ]      },      "examples": [        {          "title": "Header-Example:",          "content": "{\n  \"Authorization\": \"Bearer abc...xyz\"\n}",          "type": "String"        }      ]    },    "description": "<p>Returns an array of <code>Trials</code> belonging to the current Admin.</p>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "post",    "url": "/new/trial",    "title": "Creates a new Trial",    "name": "PostNewTrial",    "version": "0.0.1",    "group": "Admin",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "post",    "url": "/validate/login",    "title": "Validate Credentials",    "name": "PostRefreshToken",    "version": "0.0.1",    "group": "Admin",    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "refresh_token",            "description": "<p>Admin's <code>refresh_token</code> issued with an earlier <code>/login</code> call.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_id",            "description": "<p><code>mrct</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_secret",            "description": "<p><code>doascience</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "scope",            "description": "<p><code>basic</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "grant_type",            "description": "<p><code>refresh_token</code></p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "token_type",            "description": "<p>The value <code>Bearer</code>.</p>"          },          {            "group": "Success 200",            "type": "Number",            "optional": false,            "field": "expires_in",            "description": "<p>An integer representing the TTL of the access token.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "access_token",            "description": "<p>A JWT signed with the authorization server’s private key.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "refresh_token",            "description": "<p>An encrypted payload that can be used to refresh the access token when it expires.</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"token_type\": \"Bearer\",\n  \"expires_in\": 3600,\n  \"access_token\": \"abc...xyz\",\n  \"refresh_token\": \"abc...xyz\"\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidCredentials",            "description": "<p>The <code>username</code> or <code>password</code> provided are incorrect.</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 401 Unauthorized\n{\n  \"error\": \"invalid_request\",\n  \"message\": \"The refresh token is invalid.\",\n  \"hint\": \"Cannot decrypt the refresh token\"\n}",          "type": "json"        }      ]    },    "description": "<p>Validates login credentials and returns OAuth access and refresh tokens.</p>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "post",    "url": "/register",    "title": "New User",    "name": "PostRegister",    "version": "0.0.1",    "group": "Admin",    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "email",            "description": "<p>Admin's email address.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "hash",            "description": "<p>Admin's hashed password address.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>Admin's name.</p>"          },          {            "group": "Parameter",            "type": "String",            "allowedValues": [              "admin"            ],            "optional": false,            "field": "role",            "description": "<p>Admin's role.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "pass",            "description": "<p><code>!! REMOVE THIS !!</code> actual password for testing.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "salt",            "description": "<p><code>!! REMOVE THIS !!</code> salt used for hashing the password.</p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "id",            "description": "<p>A Unique ID for the Admin.</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"id\": \"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx\"\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "EmailExists",            "description": "<p>The <code>email</code> provided already exists in the system.</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 409 Conflict\n{\n  \"message\": \"Email xxx@xxx.xx already exists.\"\n}",          "type": "json"        }      ]    },    "description": "<p>TODO:</p> <ul> <li>remove pass and salt from being sent and stored</li> <li>return error when one of the required fields isn't sent</li> </ul>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "post",    "url": "/validate/email",    "title": "Validate Email",    "name": "PostValidateEmail",    "version": "0.0.1",    "group": "Admin",    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "username",            "description": "<p>Admin's email address.</p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "salt",            "description": "<p>The salt that was added to the password for hashing.</p>"          }        ]      }    },    "description": "<p>Validates if an email address exists in the system.</p> <p>TODO:</p> <ul> <li>potentially get rid of sending the salt back; should be stored by FE.</li> <li>currently, returns blank object if email doesn't exist</li> </ul>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "post",    "url": "/validate/login",    "title": "Validate Credentials",    "name": "PostValidateLogin",    "version": "0.0.1",    "group": "Admin",    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "username",            "description": "<p>Admin's email address.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "password",            "description": "<p>Admin's hashed password; hashed via `sha256(pass+salt)``.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_id",            "description": "<p><code>mrct</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_secret",            "description": "<p><code>doascience</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "scope",            "description": "<p><code>basic</code></p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "grant_type",            "description": "<p><code>password</code></p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "token_type",            "description": "<p>The value <code>Bearer</code>.</p>"          },          {            "group": "Success 200",            "type": "Number",            "optional": false,            "field": "expires_in",            "description": "<p>An integer representing the TTL of the access token.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "access_token",            "description": "<p>A JWT signed with the authorization server’s private key.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "refresh_token",            "description": "<p>An encrypted payload that can be used to refresh the access token when it expires.</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"token_type\": \"Bearer\",\n  \"expires_in\": 3600,\n  \"access_token\": \"abc...xyz\",\n  \"refresh_token\": \"abc...xyz\"\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidCredentials",            "description": "<p>The <code>username</code> or <code>password</code> provided are incorrect.</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 401 Unauthorized\n{\n  \"error\": \"invalid_credentials\",\n  \"message\": \"The user credentials were incorrect.\"\n}",          "type": "json"        }      ]    },    "description": "<p>Validates login credentials and returns OAuth access and refresh tokens.</p>",    "filename": "./index.php",    "groupTitle": "Admin"  },  {    "type": "get",    "url": "/trial/:tid/surveys",    "title": "Returns a list of Surveys from a given Trial ID for a given Subject ID",    "name": "GetTrialSurveys",    "version": "0.0.1",    "group": "Subject",    "filename": "./index.php",    "groupTitle": "Subject"  },  {    "type": "post",    "url": "/register/:tid",    "title": "Registers a new Subject into a given Trial ID",    "name": "PostRegisterForTrial",    "version": "0.0.1",    "group": "Subject",    "filename": "./index.php",    "groupTitle": "Subject"  },  {    "type": "post",    "url": "/trial/:tid/survey/:sid",    "title": "Stores Survey answers to a given Trial and Survey",    "name": "PostSurveyAnswers",    "version": "0.0.1",    "group": "Subject",    "filename": "./index.php",    "groupTitle": "Subject"  },  {    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "optional": false,            "field": "varname1",            "description": "<p>No type.</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "varname2",            "description": "<p>With type.</p>"          }        ]      }    },    "type": "",    "url": "",    "version": "0.0.0",    "filename": "./doc/main.js",    "group": "_Users_groupby_Programming_mrct_api_src_doc_main_js",    "groupTitle": "_Users_groupby_Programming_mrct_api_src_doc_main_js",    "name": ""  }] });

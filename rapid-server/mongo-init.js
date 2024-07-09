// Create user with readWrite role for RAPID database
db.createUser({
  user: "myuser",
  pwd: "mypassword",
  roles: [{ role: "readWrite", db: "RAPID" }]
});

// Create Users collection with schema validation
db.createCollection("Users", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["UserType", "UserName", "Email", "PasswordHash", "UserId"],
      properties: {
        UserType: { bsonType: "string" },
        UserName: { bsonType: "string" },
        Email: { bsonType: "string" },
        PasswordHash: { bsonType: "string" },
        UserId: { bsonType: "int" }
      }
    }
  }
});

// Create Sessions collection with schema validation
db.createCollection("Sessions", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["SessionName", "StartTime", "EndTime"],
      properties: {
        MainInvigilatorId: { bsonType: "int" },
        SessionName: { bsonType: "string" },
        StartTime: { bsonType: "date" },
        EndTime: { bsonType: "date" },
        Duration: { bsonType: "number" },
        BlacklistedApps: { bsonType: "array" },
        WhitelistedApps: { bsonType: "array" },
        CreatedAt: { bsonType: "date" }
      }
    }
  }
});

// Create Students collection with schema validation
db.createCollection("Students", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["StudentId", "StudentName", "Email", "SessionId"],
      properties: {
        StudentId: { bsonType: "int" },
        StudentName: { bsonType: "string" },
        Email: { bsonType: "string" },
        SessionId: { bsonType: "int" }
      }
    }
  }
});

// Create Screenshots collection with schema validation
db.createCollection("Screenshots", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["uuid", "data", "date_time"],
      properties: {
        uuid: { bsonType: "string" },
        data: { bsonType: "string" },
        date_time: { bsonType: "date" },
        CapturedAt: { bsonType: "date" },
        FlagDescription: { bsonType: "string" }
      }
    }
  }
});

// Create Snapshots collection with schema validation
db.createCollection("Snapshots", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["uuid", "data", "date_time"],
      properties: {
        uuid: { bsonType: "string" },
        data: { bsonType: "string" },
        date_time: { bsonType: "date" },
        CapturedAt: { bsonType: "date" },
        FlagDescription: { bsonType: "string" }
      }
    }
  }
});

// Create SessionInvigilators collection with schema validation
db.createCollection("SessionInvigilators", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["SessionId", "InvigilatorId"],
      properties: {
        SessionId: { bsonType: "objectId" },
        InvigilatorId: { bsonType: "objectId" }
      }
    }
  }
});

// Temporarily use process collection without studentid and sessionid to test process csv upload
// Create StudentProcesses collection with schema validation
// db.createCollection("StudentProcesses", {
//   validator: {
//     $jsonSchema: {
//       bsonType: "object",
//       required: ["StudentId", "SessionId", "ProcessName"],
//       properties: {
//         StudentId: { bsonType: "objectId" },
//         SessionId: { bsonType: "objectId" },
//         ProcessName: { bsonType: "string" },
//         CapturedAt: { bsonType: "date" },
//         FlagDescription: { bsonType: "string" }
//       }
//     }
//   }
// });

// Create StudentProcesses collection with schema validation (this is the current used process collection, need to be modified)
db.createCollection("StudentProcesses", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["ProcessName"],
      properties: {
        StudentId: { bsonType: "objectId" },
        SessionId: { bsonType: "objectId" },
        ProcessName: { bsonType: "string" },
        CapturedAt: { bsonType: "date" },
        FlagDescription: { bsonType: "string" }
      }
    }
  }
});

// Create a new collection to store the sequence value
db.createCollection("session_sequence")

// Insert an initial document to track the sequence
db.session_sequence.insertOne({ _id: "sessionId", sequence_value: 0 })

// Update the Sessions collection to include the SessionId field
db.Sessions.find().forEach(function(doc) {
    var sequenceValue = db.session_sequence.findAndModify({
        query: { _id: "sessionId" },
        update: { $inc: { sequence_value: 1 } },
        new: true
    }).sequence_value;

    doc.SessionId = sequenceValue;
    db.Sessions.save(doc);
});

// Create an index on the SessionId field
db.Sessions.createIndex({ SessionId: 1 })

// Create the intervals collection with validation
db.createCollection("intervals", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["uuid", "AWD", "AMD", "PL", "OW", "admin_override"],
      properties: {
        uuid: { bsonType: "string" },
        AWD: { bsonType: "int" },
        AMD: { bsonType: "int" },
        PL: { bsonType: "int" },
        OW: { bsonType: "int" },
        admin_override: { bsonType: "int" }
      }
    }
  }
});

// Create the proctoring collection with validation
db.createCollection("proctoring", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["uuid", "trigger_count", "category", "data", "date_time"],
      properties: {
        uuid: { bsonType: "string" },
        trigger_count: { bsonType: "string" },
        category: { bsonType: "string" },
        data: { bsonType: "string" },
        date_time: { bsonType: "string" }
      }
    }
  }
});

// Create the ping collection with validation
db.createCollection("ping", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["uuid", "last_connect"],
      properties: {
        uuid: { bsonType: "string" },
        last_connect: { bsonType: "string" }
      }
    }
  }
});

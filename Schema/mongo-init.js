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
      required: ["UserType", "UserName", "Email", "PasswordHash"],
      properties: {
        UserType: { bsonType: "string" },
        UserName: { bsonType: "string" },
        Email: { bsonType: "string" },
        PasswordHash: { bsonType: "string" }
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
      required: ["StudentId", "SessionId", "ScreenshotURL"],
      properties: {
        StudentId: { bsonType: "objectId" },
        SessionId: { bsonType: "objectId" },
        ScreenshotURL: { bsonType: "string" },
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

// Create Snapshots collection with schema validation
db.createCollection("Snapshots", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["StudentId", "SessionId", "SnapshotURL"],
      properties: {
        StudentId: { bsonType: "objectId" },
        SessionId: { bsonType: "objectId" },
        SnapshotURL: { bsonType: "string" },
        CapturedAt: { bsonType: "date" },
        FlagDescription: { bsonType: "string" }
      }
    }
  }
});

// Create StudentProcesses collection with schema validation
db.createCollection("StudentProcesses", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["StudentId", "SessionId", "ProcessName"],
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
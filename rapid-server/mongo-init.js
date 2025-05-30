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

// Below 4 are Data capture from Users
db.createCollection("Processes");

db.createCollection("Screenshots");

db.createCollection("Snapshots");

db.createCollection("Behaviour_logs");

// Create a new collection to store the sequence value
db.createCollection("session_sequence");

// Insert an initial document to track the sequence
db.session_sequence.insertOne({ _id: "sessionId", sequence_value: 0 });

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

//------------------- End of SE Collections -----------------//

// Create the intervals collection with validation
db.createCollection("defaults", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["name", "AWD", "AMD", "PL", "OW", "KS", "admin_override"],
      properties: {
        name: { bsonType: "string" },
        AWD: { bsonType: "int" },
        AMD: { bsonType: "int" },
        PL: { bsonType: "int" },
        OW: { bsonType: "int" },
        KS: { bsonType: "int" },
        admin_override: { bsonType: "int" }
      }
    }
  }
});

// Insert default values into the intervals collection
db.defaults.updateOne(
  { name: "intervals" },
  {
    $set: {
      AWD: 30, // set your default value here
      AMD: 30, // set your default value here
      PL: 30, // set your default value here
      OW: 30, // set your default value here
      KS: 30, // set your default value here
      admin_override: 0
    }
  },
  { upsert: true }
);

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

db.Users.insertOne({ 
  UserType: 'admin', 
  UserName: 'exampleUser', 
  Email: 'user@example.com', 
  PasswordHash: '$2a$10$mxrR.y1NFENZFX1V6yZJ8ebq1FxmGwuhOCPiY4o34odpJBZ0Kgy4q', 
  UserId: 1, 
  isActive: true 
});

db.Users.insertOne({ 
  UserType: 'invigilator', 
  UserName: 'Invigilator', 
  Email: 'invigilator@example.com', 
  PasswordHash: '$2a$10$o1UlWR7fSauwCkq4vr9KGeocx5tAcgRm/CR2xIlYLeXm0Od5fLFJe', 
  UserId: 2, 
  isActive: true 
});

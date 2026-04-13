import { app } from './app.js';
import { config } from './config.js';
import './queue/publishQueue.js';

app.listen(config.port, () => console.log(`API running on ${config.port}`));

import type { Meta, StoryObj } from "@storybook/react-vite";
import { DndContext } from "@dnd-kit/core";
import EditCanvas from "@builder/EditCanvas";
import { getPageFormat } from "@builder/lib/schema";
import { listExamples, loadExample } from "@builder/lib/examples";
import type { JsonSchema } from "@builder/types";
import schema from "./data/schema.json";
import examples from "./data/examples.json";

const typedSchema = schema as unknown as JsonSchema;
const entry = listExamples(examples)[0] ?? {
  title: "Empty",
  template: { version: 1, config: {}, rows: [] },
  data: {},
};
const model = loadExample(entry);

const meta: Meta<typeof EditCanvas> = {
  title: "Builder/Edit Canvas",
  component: EditCanvas,
  decorators: [
    (Story) => (
      <DndContext>
        <div style={{ padding: 16 }}>
          <Story />
        </div>
      </DndContext>
    ),
  ],
};

export default meta;

type Story = StoryObj<typeof EditCanvas>;

export const Invoice: Story = {
  args: {
    model,
    schema: typedSchema,
    format: getPageFormat(typedSchema),
    selectedBlockUid: null,
  },
};

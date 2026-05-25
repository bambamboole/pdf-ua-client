import type { Meta, StoryObj } from "@storybook/react-vite";
import DataView from "@builder/DataView";
import { listExamples } from "@builder/lib/examples";
import type { DataMap } from "@builder/types";
import examples from "./data/examples.json";

const data = (listExamples(examples)[0]?.data ?? {}) as unknown as DataMap;

const meta: Meta<typeof DataView> = {
  title: "Builder/Data View",
  component: DataView,
  decorators: [
    (Story) => (
      <div style={{ padding: 16 }}>
        <Story />
      </div>
    ),
  ],
};

export default meta;

type Story = StoryObj<typeof DataView>;

export const Invoice: Story = {
  args: { data },
};
